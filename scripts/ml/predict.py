#!/usr/bin/env python3
"""
Basketball ML Prediction Script
Handles individual predictions using trained scikit-learn models
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
from typing import Dict, Any, Union, List
import warnings

# Suppress sklearn warnings for cleaner output
warnings.filterwarnings('ignore')

# Import ML libraries
try:
    from sklearn.ensemble import RandomForestClassifier, RandomForestRegressor
    from sklearn.ensemble import GradientBoostingClassifier, GradientBoostingRegressor
    from sklearn.linear_model import LogisticRegression, LinearRegression
    from sklearn.svm import SVC, SVR
    from sklearn.neural_network import MLPClassifier, MLPRegressor
    from sklearn.preprocessing import StandardScaler, MinMaxScaler, RobustScaler
    from sklearn.model_selection import cross_val_score
    import xgboost as xgb
    import lightgbm as lgb
except ImportError as e:
    print(f"Error importing ML libraries: {e}", file=sys.stderr)
    sys.exit(1)

class BasketballMLPredictor:
    """
    Main prediction class for basketball analytics
    """
    
    def __init__(self, model_path: str, model_type: str, model_algorithm: str):
        self.model_path = Path(model_path)
        self.model_type = model_type
        self.model_algorithm = model_algorithm
        self.model = None
        self.scaler = None
        self.feature_names = None
        self.preprocessing_params = None
        
        # Load model and associated components
        self._load_model()
    
    def _load_model(self):
        """Load the trained model and associated components"""
        try:
            # Load main model file
            if self.model_path.suffix == '.pkl':
                with open(self.model_path, 'rb') as f:
                    model_data = pickle.load(f)
            elif self.model_path.suffix == '.joblib':
                model_data = joblib.load(self.model_path)
            else:
                raise ValueError(f"Unsupported model file format: {self.model_path.suffix}")
            
            # Extract components from model data
            if isinstance(model_data, dict):
                self.model = model_data['model']
                self.scaler = model_data.get('scaler')
                self.feature_names = model_data.get('feature_names', [])
                self.preprocessing_params = model_data.get('preprocessing_params', {})
            else:
                # Simple model without preprocessing components
                self.model = model_data
                self.feature_names = []
                self.preprocessing_params = {}
            
            print(f"Successfully loaded {self.model_algorithm} model for {self.model_type}")
            
        except Exception as e:
            raise RuntimeError(f"Failed to load model: {str(e)}")
    
    def preprocess_features(self, input_data: Dict[str, Any]) -> np.ndarray:
        """
        Preprocess input features for prediction
        """
        try:
            # Convert to DataFrame for easier manipulation
            df = pd.DataFrame([input_data])
            
            # Apply basketball-specific feature engineering
            df = self._apply_basketball_feature_engineering(df)
            
            # Handle missing values
            df = self._handle_missing_values(df)
            
            # Ensure we have the right features in the right order
            if self.feature_names:
                # Add missing features with default values
                for feature in self.feature_names:
                    if feature not in df.columns:
                        df[feature] = 0  # Default value for missing features
                
                # Select and order features
                df = df[self.feature_names]
            
            # Apply scaling if scaler is available
            if self.scaler is not None:
                features = self.scaler.transform(df)
            else:
                features = df.values
            
            return features
            
        except Exception as e:
            raise RuntimeError(f"Feature preprocessing failed: {str(e)}")
    
    def _apply_basketball_feature_engineering(self, df: pd.DataFrame) -> pd.DataFrame:
        """
        Apply basketball-specific feature engineering
        """
        # Efficiency metrics
        if 'points' in df.columns and 'field_goals_attempted' in df.columns:
            df['shooting_efficiency'] = df['points'] / df['field_goals_attempted'].replace(0, 1)
        
        if 'assists' in df.columns and 'turnovers' in df.columns:
            df['assist_to_turnover_ratio'] = df['assists'] / df['turnovers'].replace(0, 1)
        
        if 'minutes' in df.columns and 'points' in df.columns:
            df['points_per_minute'] = df['points'] / df['minutes'].replace(0, 1)
        
        # Usage and pace metrics
        if 'field_goals_attempted' in df.columns and 'team_field_goals_attempted' in df.columns:
            df['usage_rate'] = df['field_goals_attempted'] / df['team_field_goals_attempted'].replace(0, 1)
        
        # Defensive metrics
        if 'steals' in df.columns and 'blocks' in df.columns:
            df['defensive_actions'] = df['steals'] + df['blocks']
        
        # Physical load indicators (for injury risk)
        if self.model_type == 'injury_risk':
            if 'minutes_last_7_days' in df.columns and 'games_last_7_days' in df.columns:
                df['avg_minutes_per_game'] = df['minutes_last_7_days'] / df['games_last_7_days'].replace(0, 1)
            
            if 'age' in df.columns and 'experience_years' in df.columns:
                df['age_experience_interaction'] = df['age'] * df['experience_years']
        
        # Game context features
        if self.model_type == 'game_outcome':
            if 'home_wins' in df.columns and 'home_losses' in df.columns:
                df['home_win_percentage'] = df['home_wins'] / (df['home_wins'] + df['home_losses']).replace(0, 1)
            
            if 'away_wins' in df.columns and 'away_losses' in df.columns:
                df['away_win_percentage'] = df['away_wins'] / (df['away_wins'] + df['away_losses']).replace(0, 1)
        
        return df
    
    def _handle_missing_values(self, df: pd.DataFrame) -> pd.DataFrame:
        """
        Handle missing values using basketball domain knowledge
        """
        # Fill numeric columns with appropriate defaults
        numeric_columns = df.select_dtypes(include=[np.number]).columns
        
        for col in numeric_columns:
            if col.endswith('_percentage') or col.endswith('_rate'):
                df[col].fillna(0.0, inplace=True)  # Rates default to 0
            elif col.startswith('minutes'):
                df[col].fillna(0.0, inplace=True)  # Minutes default to 0
            elif col in ['age', 'height', 'weight']:
                df[col].fillna(df[col].median(), inplace=True)  # Physical stats use median
            else:
                df[col].fillna(0.0, inplace=True)  # Other numeric features default to 0
        
        # Fill categorical columns
        categorical_columns = df.select_dtypes(include=[object]).columns
        for col in categorical_columns:
            df[col].fillna('Unknown', inplace=True)
        
        return df
    
    def make_prediction(self, input_data: Dict[str, Any]) -> Dict[str, Any]:
        """
        Make a prediction using the loaded model
        """
        start_time = datetime.now()
        
        try:
            # Preprocess features
            features = self.preprocess_features(input_data)
            
            # Make prediction
            if hasattr(self.model, 'predict_proba'):
                # Classification model
                probabilities = self.model.predict_proba(features)[0]
                prediction = self.model.predict(features)[0]
                confidence = np.max(probabilities)
                
                # Get class labels
                classes = getattr(self.model, 'classes_', range(len(probabilities)))
                prob_dict = {str(cls): float(prob) for cls, prob in zip(classes, probabilities)}
                
            else:
                # Regression model
                prediction = self.model.predict(features)[0]
                probabilities = None
                prob_dict = None
                
                # Calculate confidence for regression (inverse of prediction uncertainty)
                if hasattr(self.model, 'predict') and len(features) > 1:
                    # For ensemble models, use prediction variance
                    try:
                        if hasattr(self.model, 'estimators_'):
                            individual_predictions = [est.predict(features)[0] for est in self.model.estimators_]
                            pred_std = np.std(individual_predictions)
                            confidence = max(0.1, 1.0 - min(1.0, pred_std / abs(prediction) if prediction != 0 else 1.0))
                        else:
                            confidence = 0.8  # Default confidence for single models
                    except:
                        confidence = 0.8
                else:
                    confidence = 0.8
            
            # Generate basketball-specific outputs
            basketball_output = self._generate_basketball_output(prediction, prob_dict, input_data)
            
            processing_time = (datetime.now() - start_time).total_seconds() * 1000
            
            result = {
                'prediction': float(prediction) if isinstance(prediction, (int, float, np.number)) else str(prediction),
                'confidence': float(confidence),
                'probabilities': prob_dict,
                'processing_time_ms': processing_time,
                'model_type': self.model_type,
                'model_algorithm': self.model_algorithm,
                'feature_count': len(features[0]) if len(features.shape) > 1 else len(features),
                'timestamp': datetime.now().isoformat(),
                **basketball_output
            }
            
            return result
            
        except Exception as e:
            raise RuntimeError(f"Prediction failed: {str(e)}")
    
    def _generate_basketball_output(self, prediction, probabilities, input_data: Dict[str, Any]) -> Dict[str, Any]:
        """
        Generate basketball-specific output based on model type
        """
        output = {}
        
        if self.model_type == 'player_performance':
            output.update(self._generate_player_performance_output(prediction, input_data))
        elif self.model_type == 'injury_risk':
            output.update(self._generate_injury_risk_output(prediction, probabilities, input_data))
        elif self.model_type == 'game_outcome':
            output.update(self._generate_game_outcome_output(prediction, probabilities, input_data))
        
        return output
    
    def _generate_player_performance_output(self, prediction, input_data: Dict[str, Any]) -> Dict[str, Any]:
        """Generate player performance specific output"""
        
        # If prediction is points, generate related metrics
        performance_metrics = {}
        
        if isinstance(prediction, (int, float)):
            performance_metrics['predicted_points'] = float(prediction)
            
            # Estimate other stats based on points and player characteristics
            position = input_data.get('position', 'Guard')
            
            if position in ['Point Guard', 'PG']:
                performance_metrics['predicted_assists'] = max(1, prediction * 0.3)
                performance_metrics['predicted_rebounds'] = max(1, prediction * 0.2)
            elif position in ['Shooting Guard', 'SG']:
                performance_metrics['predicted_assists'] = max(1, prediction * 0.2)
                performance_metrics['predicted_rebounds'] = max(1, prediction * 0.25)
            elif position in ['Center', 'C']:
                performance_metrics['predicted_assists'] = max(1, prediction * 0.1)
                performance_metrics['predicted_rebounds'] = max(2, prediction * 0.5)
            else:  # Forwards
                performance_metrics['predicted_assists'] = max(1, prediction * 0.25)
                performance_metrics['predicted_rebounds'] = max(2, prediction * 0.4)
        
        return {
            'performance_metrics': performance_metrics,
            'category': self._categorize_performance(prediction),
        }
    
    def _generate_injury_risk_output(self, prediction, probabilities, input_data: Dict[str, Any]) -> Dict[str, Any]:
        """Generate injury risk specific output"""
        
        injury_probability = float(prediction) if isinstance(prediction, (int, float)) else probabilities.get('1', 0.0)
        
        # Identify risk factors
        risk_factors = []
        recommendations = []
        
        age = input_data.get('age', 25)
        minutes_last_7 = input_data.get('minutes_last_7_days', 0)
        games_last_7 = input_data.get('games_last_7_days', 0)
        
        if age > 30:
            risk_factors.append({'factor': 'Age', 'value': age, 'impact': 'high'})
            recommendations.append({
                'action': 'Increase recovery time',
                'priority': 'high',
                'description': 'Older players need more recovery between games'
            })
        
        if minutes_last_7 > 240:  # More than 240 minutes in last 7 days
            risk_factors.append({'factor': 'High minutes load', 'value': minutes_last_7, 'impact': 'medium'})
            recommendations.append({
                'action': 'Monitor playing time',
                'priority': 'medium',
                'description': 'Consider reducing minutes in next few games'
            })
        
        if games_last_7 > 4:
            risk_factors.append({'factor': 'High game frequency', 'value': games_last_7, 'impact': 'medium'})
            recommendations.append({
                'action': 'Rest consideration',
                'priority': 'medium',
                'description': 'Consider rest day or reduced role'
            })
        
        if injury_probability > 0.7:
            recommendations.append({
                'action': 'Medical evaluation',
                'priority': 'urgent',
                'description': 'Schedule immediate medical assessment'
            })
        
        return {
            'injury_probability': injury_probability,
            'risk_factors': risk_factors,
            'recommendations': recommendations,
            'category': self._categorize_injury_risk(injury_probability),
        }
    
    def _generate_game_outcome_output(self, prediction, probabilities, input_data: Dict[str, Any]) -> Dict[str, Any]:
        """Generate game outcome specific output"""
        
        if probabilities:
            win_prob = probabilities.get('1', probabilities.get('Win', 0.5))
        else:
            win_prob = float(prediction) if isinstance(prediction, (int, float)) else 0.5
        
        return {
            'win_probability': win_prob,
            'predicted_outcome': 'Win' if win_prob > 0.5 else 'Loss',
            'confidence_level': 'High' if abs(win_prob - 0.5) > 0.3 else 'Medium' if abs(win_prob - 0.5) > 0.1 else 'Low',
            'category': self._categorize_game_outcome(win_prob),
        }
    
    def _categorize_performance(self, prediction) -> str:
        """Categorize player performance prediction"""
        if isinstance(prediction, (int, float)):
            if prediction >= 25:
                return 'Excellent'
            elif prediction >= 20:
                return 'Very Good'
            elif prediction >= 15:
                return 'Good'
            elif prediction >= 10:
                return 'Average'
            else:
                return 'Below Average'
        return 'Unknown'
    
    def _categorize_injury_risk(self, probability: float) -> str:
        """Categorize injury risk level"""
        if probability >= 0.8:
            return 'Very High Risk'
        elif probability >= 0.6:
            return 'High Risk'
        elif probability >= 0.4:
            return 'Medium Risk'
        elif probability >= 0.2:
            return 'Low Risk'
        else:
            return 'Very Low Risk'
    
    def _categorize_game_outcome(self, win_probability: float) -> str:
        """Categorize game outcome prediction"""
        if win_probability >= 0.8:
            return 'Strong Favorite'
        elif win_probability >= 0.65:
            return 'Favorite'
        elif win_probability >= 0.55:
            return 'Slight Favorite'
        elif win_probability >= 0.45:
            return 'Even'
        elif win_probability >= 0.35:
            return 'Slight Underdog'
        elif win_probability >= 0.2:
            return 'Underdog'
        else:
            return 'Strong Underdog'


def main():
    """Main function to handle command line prediction"""
    parser = argparse.ArgumentParser(description='Basketball ML Prediction')
    parser.add_argument('--model-path', required=True, help='Path to trained model file')
    parser.add_argument('--input-file', required=True, help='Path to input JSON file')
    parser.add_argument('--output-file', required=True, help='Path to output JSON file')
    parser.add_argument('--model-type', required=True, help='Type of model (player_performance, injury_risk, game_outcome)')
    parser.add_argument('--model-algorithm', required=True, help='Algorithm used (random_forest, logistic_regression, etc.)')
    
    args = parser.parse_args()
    
    try:
        # Load input data
        with open(args.input_file, 'r') as f:
            input_data = json.load(f)
        
        # Initialize predictor
        predictor = BasketballMLPredictor(args.model_path, args.model_type, args.model_algorithm)
        
        # Make prediction
        result = predictor.make_prediction(input_data)
        
        # Save result
        with open(args.output_file, 'w') as f:
            json.dump(result, f, indent=2, default=str)
        
        print(f"Prediction completed successfully. Output saved to {args.output_file}")
        
    except Exception as e:
        error_result = {
            'error': str(e),
            'traceback': traceback.format_exc(),
            'timestamp': datetime.now().isoformat(),
        }
        
        # Save error to output file
        with open(args.output_file, 'w') as f:
            json.dump(error_result, f, indent=2)
        
        print(f"Error occurred: {str(e)}", file=sys.stderr)
        sys.exit(1)


if __name__ == '__main__':
    main()