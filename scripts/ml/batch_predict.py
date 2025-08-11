#!/usr/bin/env python3
"""
Basketball ML Batch Prediction Script
Handles batch predictions for multiple entities efficiently
"""

import argparse
import json
import pickle
import joblib
import numpy as np
import pandas as pd
import sys
import traceback
from datetime import datetime
from pathlib import Path
from typing import Dict, Any, List
import warnings
from concurrent.futures import ThreadPoolExecutor, as_completed
import multiprocessing as mp

# Suppress sklearn warnings
warnings.filterwarnings('ignore')

# Import the predictor class from predict.py
from predict import BasketballMLPredictor

class BasketballBatchPredictor(BasketballMLPredictor):
    """
    Batch prediction class extending the single prediction functionality
    """
    
    def __init__(self, model_path: str, model_type: str, model_algorithm: str):
        super().__init__(model_path, model_type, model_algorithm)
        self.batch_size = 100  # Process in batches to manage memory
        self.max_workers = min(4, mp.cpu_count())  # Limit concurrent threads
    
    def make_batch_predictions(self, batch_input_data: List[Dict[str, Any]]) -> List[Dict[str, Any]]:
        """
        Make predictions for a batch of input data
        """
        start_time = datetime.now()
        total_samples = len(batch_input_data)
        
        try:
            print(f"Processing batch of {total_samples} samples using {self.max_workers} workers")
            
            # Process in chunks to manage memory
            results = []
            
            for i in range(0, total_samples, self.batch_size):
                batch_chunk = batch_input_data[i:i + self.batch_size]
                chunk_results = self._process_batch_chunk(batch_chunk)
                results.extend(chunk_results)
                
                # Progress update
                processed = min(i + self.batch_size, total_samples)
                print(f"Processed {processed}/{total_samples} samples")
            
            processing_time = (datetime.now() - start_time).total_seconds()
            
            # Add batch metadata to results
            batch_metadata = {
                'total_samples': total_samples,
                'batch_processing_time_seconds': processing_time,
                'average_time_per_sample_ms': (processing_time * 1000) / total_samples,
                'model_type': self.model_type,
                'model_algorithm': self.model_algorithm,
                'timestamp': datetime.now().isoformat(),
                'success_rate': sum(1 for r in results if 'error' not in r) / len(results),
            }
            
            return {
                'predictions': results,
                'batch_metadata': batch_metadata,
            }
            
        except Exception as e:
            raise RuntimeError(f"Batch prediction failed: {str(e)}")
    
    def _process_batch_chunk(self, batch_chunk: List[Dict[str, Any]]) -> List[Dict[str, Any]]:
        """
        Process a chunk of the batch using parallel processing
        """
        results = []
        
        # For small batches, use sequential processing
        if len(batch_chunk) <= 10:
            for i, input_data in enumerate(batch_chunk):
                try:
                    result = self.make_prediction(input_data)
                    result['batch_index'] = i
                    results.append(result)
                except Exception as e:
                    error_result = {
                        'batch_index': i,
                        'error': str(e),
                        'input_data_preview': str(input_data)[:200] + '...' if len(str(input_data)) > 200 else str(input_data),
                        'timestamp': datetime.now().isoformat(),
                    }
                    results.append(error_result)
            
            return results
        
        # For larger batches, use parallel processing
        with ThreadPoolExecutor(max_workers=self.max_workers) as executor:
            # Submit all prediction tasks
            future_to_index = {
                executor.submit(self._safe_prediction, input_data, i): i 
                for i, input_data in enumerate(batch_chunk)
            }
            
            # Collect results as they complete
            for future in as_completed(future_to_index):
                index = future_to_index[future]
                try:
                    result = future.result()
                    result['batch_index'] = index
                    results.append(result)
                except Exception as e:
                    error_result = {
                        'batch_index': index,
                        'error': str(e),
                        'input_data_preview': str(batch_chunk[index])[:200] + '...',
                        'timestamp': datetime.now().isoformat(),
                    }
                    results.append(error_result)
        
        # Sort results by batch_index to maintain order
        results.sort(key=lambda x: x.get('batch_index', 0))
        
        return results
    
    def _safe_prediction(self, input_data: Dict[str, Any], index: int) -> Dict[str, Any]:
        """
        Safely make a prediction with error handling
        """
        try:
            return self.make_prediction(input_data)
        except Exception as e:
            return {
                'error': str(e),
                'input_data_preview': str(input_data)[:200] + '...' if len(str(input_data)) > 200 else str(input_data),
                'timestamp': datetime.now().isoformat(),
            }
    
    def make_optimized_batch_predictions(self, batch_input_data: List[Dict[str, Any]]) -> List[Dict[str, Any]]:
        """
        Optimized batch prediction using vectorized operations where possible
        """
        start_time = datetime.now()
        
        try:
            # Convert all input data to DataFrame for vectorized processing
            df_list = []
            valid_indices = []
            
            for i, input_data in enumerate(batch_input_data):
                try:
                    df_row = pd.DataFrame([input_data])
                    df_list.append(df_row)
                    valid_indices.append(i)
                except Exception as e:
                    print(f"Warning: Could not process input {i}: {e}")
            
            if not df_list:
                raise ValueError("No valid input data to process")
            
            # Combine all data
            batch_df = pd.concat(df_list, ignore_index=True)
            
            # Apply basketball feature engineering to entire batch
            batch_df = self._apply_basketball_feature_engineering(batch_df)
            batch_df = self._handle_missing_values(batch_df)
            
            # Ensure we have the right features
            if self.feature_names:
                for feature in self.feature_names:
                    if feature not in batch_df.columns:
                        batch_df[feature] = 0
                batch_df = batch_df[self.feature_names]
            
            # Apply scaling to entire batch
            if self.scaler is not None:
                batch_features = self.scaler.transform(batch_df)
            else:
                batch_features = batch_df.values
            
            # Make batch predictions
            if hasattr(self.model, 'predict_proba'):
                # Classification
                batch_probabilities = self.model.predict_proba(batch_features)
                batch_predictions = self.model.predict(batch_features)
                batch_confidences = np.max(batch_probabilities, axis=1)
                classes = getattr(self.model, 'classes_', range(batch_probabilities.shape[1]))
            else:
                # Regression
                batch_predictions = self.model.predict(batch_features)
                batch_probabilities = None
                batch_confidences = np.full(len(batch_predictions), 0.8)  # Default confidence
                classes = None
            
            # Format results
            results = []
            processing_time = (datetime.now() - start_time).total_seconds() * 1000
            
            for i, valid_idx in enumerate(valid_indices):
                prediction = batch_predictions[i]
                confidence = float(batch_confidences[i])
                
                if batch_probabilities is not None:
                    prob_dict = {str(cls): float(batch_probabilities[i][j]) for j, cls in enumerate(classes)}
                else:
                    prob_dict = None
                
                # Generate basketball-specific output
                basketball_output = self._generate_basketball_output(
                    prediction, prob_dict, batch_input_data[valid_idx]
                )
                
                result = {
                    'prediction': float(prediction) if isinstance(prediction, (int, float, np.number)) else str(prediction),
                    'confidence': confidence,
                    'probabilities': prob_dict,
                    'processing_time_ms': processing_time / len(valid_indices),  # Approximate per-sample time
                    'model_type': self.model_type,
                    'model_algorithm': self.model_algorithm,
                    'batch_index': valid_idx,
                    'timestamp': datetime.now().isoformat(),
                    **basketball_output
                }
                
                results.append(result)
            
            # Fill in results for invalid indices
            full_results = []
            result_idx = 0
            
            for original_idx in range(len(batch_input_data)):
                if original_idx in valid_indices:
                    full_results.append(results[result_idx])
                    result_idx += 1
                else:
                    full_results.append({
                        'error': 'Invalid input data',
                        'batch_index': original_idx,
                        'timestamp': datetime.now().isoformat(),
                    })
            
            return {
                'predictions': full_results,
                'batch_metadata': {
                    'total_samples': len(batch_input_data),
                    'valid_samples': len(valid_indices),
                    'batch_processing_time_seconds': (datetime.now() - start_time).total_seconds(),
                    'average_time_per_sample_ms': processing_time / len(valid_indices) if valid_indices else 0,
                    'model_type': self.model_type,
                    'model_algorithm': self.model_algorithm,
                    'optimization_used': True,
                    'timestamp': datetime.now().isoformat(),
                }
            }
            
        except Exception as e:
            # Fallback to individual predictions
            print(f"Optimized batch prediction failed, falling back to individual predictions: {e}")
            return self.make_batch_predictions(batch_input_data)
    
    def analyze_batch_results(self, results: Dict[str, Any]) -> Dict[str, Any]:
        """
        Analyze batch prediction results to provide insights
        """
        predictions = results['predictions']
        analysis = {
            'total_predictions': len(predictions),
            'successful_predictions': sum(1 for p in predictions if 'error' not in p),
            'failed_predictions': sum(1 for p in predictions if 'error' in p),
            'average_confidence': 0.0,
            'confidence_distribution': {'high': 0, 'medium': 0, 'low': 0},
            'model_performance': {},
        }
        
        successful_predictions = [p for p in predictions if 'error' not in p]
        
        if successful_predictions:
            # Calculate average confidence
            confidences = [p.get('confidence', 0) for p in successful_predictions]
            analysis['average_confidence'] = np.mean(confidences)
            
            # Confidence distribution
            for conf in confidences:
                if conf >= 0.8:
                    analysis['confidence_distribution']['high'] += 1
                elif conf >= 0.6:
                    analysis['confidence_distribution']['medium'] += 1
                else:
                    analysis['confidence_distribution']['low'] += 1
            
            # Model-specific analysis
            if self.model_type == 'player_performance':
                predicted_points = [
                    p.get('performance_metrics', {}).get('predicted_points', 0)
                    for p in successful_predictions
                ]
                analysis['model_performance'] = {
                    'avg_predicted_points': np.mean(predicted_points),
                    'min_predicted_points': np.min(predicted_points),
                    'max_predicted_points': np.max(predicted_points),
                    'std_predicted_points': np.std(predicted_points),
                }
            
            elif self.model_type == 'injury_risk':
                injury_probs = [
                    p.get('injury_probability', 0)
                    for p in successful_predictions
                ]
                high_risk_count = sum(1 for prob in injury_probs if prob >= 0.7)
                analysis['model_performance'] = {
                    'avg_injury_probability': np.mean(injury_probs),
                    'high_risk_players': high_risk_count,
                    'high_risk_percentage': high_risk_count / len(injury_probs) * 100,
                }
            
            elif self.model_type == 'game_outcome':
                win_probs = [
                    p.get('win_probability', 0.5)
                    for p in successful_predictions
                ]
                analysis['model_performance'] = {
                    'avg_win_probability': np.mean(win_probs),
                    'strong_predictions': sum(1 for prob in win_probs if abs(prob - 0.5) > 0.3),
                    'uncertain_predictions': sum(1 for prob in win_probs if abs(prob - 0.5) < 0.1),
                }
        
        return analysis


def main():
    """Main function for batch prediction"""
    parser = argparse.ArgumentParser(description='Basketball ML Batch Prediction')
    parser.add_argument('--model-path', required=True, help='Path to trained model file')
    parser.add_argument('--input-file', required=True, help='Path to input JSON file with batch data')
    parser.add_argument('--output-file', required=True, help='Path to output JSON file')
    parser.add_argument('--model-type', required=True, help='Type of model')
    parser.add_argument('--model-algorithm', required=True, help='Algorithm used')
    parser.add_argument('--optimize', action='store_true', help='Use optimized batch processing')
    parser.add_argument('--max-workers', type=int, default=4, help='Maximum number of worker threads')
    
    args = parser.parse_args()
    
    try:
        # Load batch input data
        with open(args.input_file, 'r') as f:
            input_data = json.load(f)
        
        batch_input_data = input_data.get('batch_data', input_data)
        if not isinstance(batch_input_data, list):
            raise ValueError("Input must contain 'batch_data' as a list or be a list itself")
        
        # Initialize batch predictor
        predictor = BasketballBatchPredictor(args.model_path, args.model_type, args.model_algorithm)
        predictor.max_workers = args.max_workers
        
        # Make batch predictions
        if args.optimize:
            result = predictor.make_optimized_batch_predictions(batch_input_data)
        else:
            result = predictor.make_batch_predictions(batch_input_data)
        
        # Add analysis
        result['analysis'] = predictor.analyze_batch_results(result)
        
        # Save result
        with open(args.output_file, 'w') as f:
            json.dump(result, f, indent=2, default=str)
        
        print(f"Batch prediction completed successfully. {len(batch_input_data)} samples processed.")
        print(f"Success rate: {result.get('analysis', {}).get('successful_predictions', 0)}/{len(batch_input_data)}")
        print(f"Output saved to {args.output_file}")
        
    except Exception as e:
        error_result = {
            'error': str(e),
            'traceback': traceback.format_exc(),
            'timestamp': datetime.now().isoformat(),
            'batch_metadata': {
                'total_samples': 0,
                'processing_failed': True,
            }
        }
        
        with open(args.output_file, 'w') as f:
            json.dump(error_result, f, indent=2)
        
        print(f"Batch prediction error: {str(e)}", file=sys.stderr)
        sys.exit(1)


if __name__ == '__main__':
    main()