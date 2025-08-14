"""
Model Evaluation und Validation für Basketball Analytics ML-Modelle
Umfassende Evaluation mit Basketball-spezifischen Metriken
"""

import pandas as pd
import numpy as np
import matplotlib.pyplot as plt
import seaborn as sns
import logging
from typing import Dict, List, Tuple, Any, Optional, Union
from sklearn.metrics import (
    accuracy_score, precision_score, recall_score, f1_score,
    roc_auc_score, roc_curve, precision_recall_curve, confusion_matrix,
    classification_report, mean_squared_error, mean_absolute_error,
    r2_score, explained_variance_score
)
from sklearn.model_selection import cross_validate, learning_curve, validation_curve
from sklearn.calibration import calibration_curve
import joblib
from pathlib import Path
import warnings
warnings.filterwarnings('ignore')

logger = logging.getLogger(__name__)

class ModelEvaluator:
    """
    Umfassende Model Evaluation für Basketball ML-Modelle
    """
    
    def __init__(self, config: Dict[str, Any]):
        """
        Initialize Model Evaluator
        
        Args:
            config: Konfiguration für Evaluation
        """
        self.config = config
        self.results_dir = Path(config.get('results_dir', 'evaluation_results'))
        self.results_dir.mkdir(exist_ok=True)
        
        self.evaluation_history = []
        
    def evaluate_model(
        self,
        model: Any,
        X_test: pd.DataFrame,
        y_test: pd.Series,
        model_name: str = "model",
        include_plots: bool = True
    ) -> Dict[str, Any]:
        """
        Hauptmethode für Model Evaluation
        
        Args:
            model: Trainiertes ML-Model
            X_test: Test Features
            y_test: Test Target
            model_name: Name des Models
            include_plots: Erstelle Visualisierungen
            
        Returns:
            Evaluation-Ergebnisse
        """
        logger.info(f"Starte Model Evaluation für {model_name}")
        
        # Problem Type bestimmen
        problem_type = self._determine_problem_type(y_test)
        
        # Predictions generieren
        y_pred = model.predict(X_test)
        
        if hasattr(model, 'predict_proba') and problem_type != 'regression':
            y_pred_proba = model.predict_proba(X_test)
            if y_pred_proba.shape[1] == 2:  # Binary Classification
                y_pred_proba = y_pred_proba[:, 1]
        else:
            y_pred_proba = None
        
        # Basis-Metriken berechnen
        if problem_type == 'regression':
            metrics = self._calculate_regression_metrics(y_test, y_pred)
        else:
            metrics = self._calculate_classification_metrics(
                y_test, y_pred, y_pred_proba, problem_type
            )
        
        # Basketball-spezifische Metriken
        basketball_metrics = self._calculate_basketball_metrics(
            y_test, y_pred, y_pred_proba, problem_type
        )
        
        # Feature Importance (falls verfügbar)
        feature_importance = self._get_feature_importance(model, X_test.columns)
        
        # Prediction Distribution
        pred_distribution = self._analyze_prediction_distribution(y_test, y_pred)
        
        # Residual Analysis (für Regression)
        residual_analysis = None
        if problem_type == 'regression':
            residual_analysis = self._analyze_residuals(y_test, y_pred)
        
        # Evaluation Results zusammenfassen
        evaluation_results = {
            'model_name': model_name,
            'problem_type': problem_type,
            'basic_metrics': metrics,
            'basketball_metrics': basketball_metrics,
            'feature_importance': feature_importance,
            'prediction_distribution': pred_distribution,
            'residual_analysis': residual_analysis
        }
        
        # Visualisierungen erstellen
        if include_plots:
            plots = self._create_evaluation_plots(
                y_test, y_pred, y_pred_proba, 
                problem_type, model_name, feature_importance
            )
            evaluation_results['plots'] = plots
        
        # Evaluation History update
        self.evaluation_history.append(evaluation_results)
        
        logger.info(f"Model Evaluation abgeschlossen für {model_name}")
        
        return evaluation_results
    
    def _determine_problem_type(self, y: pd.Series) -> str:
        """
        Bestimme Problem Type
        """
        unique_values = y.nunique()
        
        if unique_values == 2:
            return 'binary_classification'
        elif unique_values <= 10 and y.dtype in ['int64', 'object']:
            return 'multiclass_classification'
        else:
            return 'regression'
    
    def _calculate_regression_metrics(
        self, 
        y_true: pd.Series, 
        y_pred: np.ndarray
    ) -> Dict[str, float]:
        """
        Berechne Regression-Metriken
        """
        return {
            'mse': mean_squared_error(y_true, y_pred),
            'rmse': np.sqrt(mean_squared_error(y_true, y_pred)),
            'mae': mean_absolute_error(y_true, y_pred),
            'r2_score': r2_score(y_true, y_pred),
            'explained_variance': explained_variance_score(y_true, y_pred),
            'mean_absolute_percentage_error': np.mean(
                np.abs((y_true - y_pred) / np.maximum(np.abs(y_true), 1e-8))
            ) * 100
        }
    
    def _calculate_classification_metrics(
        self,
        y_true: pd.Series,
        y_pred: np.ndarray,
        y_pred_proba: Optional[np.ndarray],
        problem_type: str
    ) -> Dict[str, float]:
        """
        Berechne Classification-Metriken
        """
        metrics = {
            'accuracy': accuracy_score(y_true, y_pred),
            'precision_macro': precision_score(y_true, y_pred, average='macro', zero_division=0),
            'recall_macro': recall_score(y_true, y_pred, average='macro', zero_division=0),
            'f1_macro': f1_score(y_true, y_pred, average='macro', zero_division=0)
        }
        
        if problem_type == 'binary_classification':
            metrics.update({
                'precision_binary': precision_score(y_true, y_pred, zero_division=0),
                'recall_binary': recall_score(y_true, y_pred, zero_division=0),
                'f1_binary': f1_score(y_true, y_pred, zero_division=0)
            })
            
            if y_pred_proba is not None:
                metrics['roc_auc'] = roc_auc_score(y_true, y_pred_proba)
        
        elif problem_type == 'multiclass_classification':
            if y_pred_proba is not None and len(np.unique(y_true)) > 2:
                try:
                    metrics['roc_auc_ovr'] = roc_auc_score(
                        y_true, y_pred_proba, multi_class='ovr'
                    )
                except ValueError:
                    pass
        
        return metrics
    
    def _calculate_basketball_metrics(
        self,
        y_true: pd.Series,
        y_pred: np.ndarray,
        y_pred_proba: Optional[np.ndarray],
        problem_type: str
    ) -> Dict[str, Any]:
        """
        Basketball-spezifische Evaluation-Metriken
        """
        basketball_metrics = {}
        
        if problem_type != 'regression':
            # Classification-spezifische Basketball-Metriken
            
            # Win/Loss Prediction Accuracy (falls das das Target ist)
            if set(y_true.unique()).issubset({0, 1}) and problem_type == 'binary_classification':
                basketball_metrics['win_prediction_accuracy'] = accuracy_score(y_true, y_pred)
                
                # Streak Analysis
                streaks = self._analyze_prediction_streaks(y_true, y_pred)
                basketball_metrics['streak_analysis'] = streaks
            
            # Shot Success Prediction (falls das das Target ist)
            if y_pred_proba is not None:
                # Calibration für Shooting Percentage Predictions
                calibration_stats = self._analyze_prediction_calibration(
                    y_true, y_pred_proba
                )
                basketball_metrics['calibration'] = calibration_stats
            
            # Performance by Score Margin (falls Game-Daten verfügbar)
            margin_analysis = self._analyze_performance_by_margin(y_true, y_pred)
            basketball_metrics['margin_analysis'] = margin_analysis
            
        else:
            # Regression-spezifische Basketball-Metriken
            
            # Points Prediction Analysis
            if 'points' in str(y_true.name).lower():
                point_metrics = self._analyze_points_prediction(y_true, y_pred)
                basketball_metrics['points_analysis'] = point_metrics
            
            # Performance Metrics Prediction
            perf_metrics = self._analyze_performance_prediction(y_true, y_pred)
            basketball_metrics['performance_analysis'] = perf_metrics
        
        return basketball_metrics
    
    def _analyze_prediction_streaks(
        self, 
        y_true: pd.Series, 
        y_pred: np.ndarray
    ) -> Dict[str, Any]:
        """
        Analysiere Win/Loss Streaks in Predictions
        """
        correct_predictions = (y_true == y_pred).astype(int)
        
        # Streak-Längen berechnen
        streaks = []
        current_streak = 1
        
        for i in range(1, len(correct_predictions)):
            if correct_predictions.iloc[i] == correct_predictions.iloc[i-1]:
                current_streak += 1
            else:
                streaks.append(current_streak)
                current_streak = 1
        streaks.append(current_streak)
        
        return {
            'average_streak_length': np.mean(streaks),
            'max_streak_length': max(streaks),
            'total_streaks': len(streaks),
            'accuracy_consistency': np.std(correct_predictions)
        }
    
    def _analyze_prediction_calibration(
        self,
        y_true: pd.Series,
        y_pred_proba: np.ndarray
    ) -> Dict[str, Any]:
        """
        Analysiere Prediction Calibration für Basketball
        """
        try:
            fraction_of_positives, mean_predicted_value = calibration_curve(
                y_true, y_pred_proba, n_bins=10
            )
            
            # Calibration Error berechnen
            calibration_error = np.mean(
                np.abs(fraction_of_positives - mean_predicted_value)
            )
            
            return {
                'calibration_error': calibration_error,
                'fraction_of_positives': fraction_of_positives.tolist(),
                'mean_predicted_value': mean_predicted_value.tolist(),
                'is_well_calibrated': calibration_error < 0.1
            }
        except Exception as e:
            logger.warning(f"Calibration analysis failed: {e}")
            return {'error': str(e)}
    
    def _analyze_performance_by_margin(
        self,
        y_true: pd.Series,
        y_pred: np.ndarray
    ) -> Dict[str, Any]:
        """
        Analysiere Performance nach Score-Margin
        """
        # Simuliere Score-Margins (in echter Anwendung wären diese verfügbar)
        margins = ['close_game', 'moderate_margin', 'blowout']
        margin_performance = {}
        
        # Aufteilen der Daten in Margin-Kategorien (vereinfacht)
        data_size = len(y_true)
        close_games = slice(0, data_size // 3)
        moderate_games = slice(data_size // 3, 2 * data_size // 3)
        blowout_games = slice(2 * data_size // 3, data_size)
        
        for margin, game_slice in zip(margins, [close_games, moderate_games, blowout_games]):
            y_true_margin = y_true.iloc[game_slice]
            y_pred_margin = y_pred[game_slice]
            
            if len(y_true_margin) > 0:
                margin_performance[margin] = {
                    'accuracy': accuracy_score(y_true_margin, y_pred_margin),
                    'sample_size': len(y_true_margin)
                }
        
        return margin_performance
    
    def _analyze_points_prediction(
        self,
        y_true: pd.Series,
        y_pred: np.ndarray
    ) -> Dict[str, Any]:
        """
        Spezielle Analyse für Points Prediction
        """
        residuals = y_true - y_pred
        
        # Shooting Performance Categories
        low_scoring = y_true <= y_true.quantile(0.33)
        medium_scoring = (y_true > y_true.quantile(0.33)) & (y_true <= y_true.quantile(0.66))
        high_scoring = y_true > y_true.quantile(0.66)
        
        category_performance = {}
        for category, mask in zip(['low', 'medium', 'high'], [low_scoring, medium_scoring, high_scoring]):
            if mask.sum() > 0:
                category_residuals = residuals[mask]
                category_performance[category] = {
                    'mae': np.mean(np.abs(category_residuals)),
                    'bias': np.mean(category_residuals),
                    'samples': mask.sum()
                }
        
        return {
            'overall_mae': np.mean(np.abs(residuals)),
            'overall_bias': np.mean(residuals),
            'category_performance': category_performance,
            'prediction_within_5_points': np.mean(np.abs(residuals) <= 5) * 100
        }
    
    def _analyze_performance_prediction(
        self,
        y_true: pd.Series,
        y_pred: np.ndarray
    ) -> Dict[str, Any]:
        """
        Analyse für Performance Metrics Prediction
        """
        residuals = y_true - y_pred
        
        return {
            'prediction_accuracy_bands': {
                'within_10_percent': np.mean(
                    np.abs(residuals) <= 0.1 * np.abs(y_true)
                ) * 100,
                'within_20_percent': np.mean(
                    np.abs(residuals) <= 0.2 * np.abs(y_true)
                ) * 100,
                'within_50_percent': np.mean(
                    np.abs(residuals) <= 0.5 * np.abs(y_true)
                ) * 100
            },
            'over_prediction_tendency': np.mean(residuals < 0) * 100,
            'under_prediction_tendency': np.mean(residuals > 0) * 100
        }
    
    def _get_feature_importance(
        self,
        model: Any,
        feature_names: List[str]
    ) -> Optional[Dict[str, float]]:
        """
        Extrahiere Feature Importance vom Model
        """
        try:
            if hasattr(model, 'feature_importances_'):
                return dict(zip(feature_names, model.feature_importances_))
            elif hasattr(model, 'coef_'):
                return dict(zip(feature_names, np.abs(model.coef_.flatten())))
            else:
                return None
        except Exception as e:
            logger.warning(f"Could not extract feature importance: {e}")
            return None
    
    def _analyze_prediction_distribution(
        self,
        y_true: pd.Series,
        y_pred: np.ndarray
    ) -> Dict[str, Any]:
        """
        Analysiere Prediction Distribution
        """
        return {
            'true_distribution': {
                'mean': float(y_true.mean()),
                'std': float(y_true.std()),
                'min': float(y_true.min()),
                'max': float(y_true.max())
            },
            'pred_distribution': {
                'mean': float(np.mean(y_pred)),
                'std': float(np.std(y_pred)),
                'min': float(np.min(y_pred)),
                'max': float(np.max(y_pred))
            },
            'distribution_similarity': {
                'mean_difference': float(np.abs(y_true.mean() - np.mean(y_pred))),
                'std_difference': float(np.abs(y_true.std() - np.std(y_pred)))
            }
        }
    
    def _analyze_residuals(
        self,
        y_true: pd.Series,
        y_pred: np.ndarray
    ) -> Dict[str, Any]:
        """
        Residual Analysis für Regression
        """
        residuals = y_true - y_pred
        
        return {
            'residual_stats': {
                'mean': float(residuals.mean()),
                'std': float(residuals.std()),
                'skewness': float(residuals.skew()),
                'kurtosis': float(residuals.kurtosis())
            },
            'normality_test': {
                'shapiro_pvalue': float(
                    __import__('scipy.stats').stats.shapiro(residuals.sample(min(5000, len(residuals))))[1]
                )
            },
            'heteroscedasticity': {
                'residual_pred_correlation': float(np.corrcoef(residuals, y_pred)[0, 1])
            }
        }
    
    def _create_evaluation_plots(
        self,
        y_true: pd.Series,
        y_pred: np.ndarray,
        y_pred_proba: Optional[np.ndarray],
        problem_type: str,
        model_name: str,
        feature_importance: Optional[Dict[str, float]]
    ) -> Dict[str, str]:
        """
        Erstelle Evaluation Plots
        """
        plots = {}
        
        plt.style.use('seaborn-v0_8')
        
        if problem_type == 'regression':
            # Regression Plots
            plots.update(self._create_regression_plots(
                y_true, y_pred, model_name
            ))
        else:
            # Classification Plots
            plots.update(self._create_classification_plots(
                y_true, y_pred, y_pred_proba, model_name, problem_type
            ))
        
        # Feature Importance Plot
        if feature_importance:
            plots['feature_importance'] = self._create_feature_importance_plot(
                feature_importance, model_name
            )
        
        return plots
    
    def _create_regression_plots(
        self,
        y_true: pd.Series,
        y_pred: np.ndarray,
        model_name: str
    ) -> Dict[str, str]:
        """
        Erstelle Regression-spezifische Plots
        """
        plots = {}
        
        # Predicted vs Actual
        fig, (ax1, ax2) = plt.subplots(1, 2, figsize=(15, 6))
        
        # Scatter Plot
        ax1.scatter(y_true, y_pred, alpha=0.6)
        ax1.plot([y_true.min(), y_true.max()], [y_true.min(), y_true.max()], 'r--', lw=2)
        ax1.set_xlabel('Actual Values')
        ax1.set_ylabel('Predicted Values')
        ax1.set_title(f'{model_name} - Predicted vs Actual')
        
        # Residuals Plot
        residuals = y_true - y_pred
        ax2.scatter(y_pred, residuals, alpha=0.6)
        ax2.axhline(y=0, color='r', linestyle='--')
        ax2.set_xlabel('Predicted Values')
        ax2.set_ylabel('Residuals')
        ax2.set_title(f'{model_name} - Residuals Plot')
        
        plt.tight_layout()
        plot_path = self.results_dir / f'{model_name}_regression_plots.png'
        plt.savefig(plot_path, dpi=300, bbox_inches='tight')
        plt.close()
        
        plots['regression_analysis'] = str(plot_path)
        
        return plots
    
    def _create_classification_plots(
        self,
        y_true: pd.Series,
        y_pred: np.ndarray,
        y_pred_proba: Optional[np.ndarray],
        model_name: str,
        problem_type: str
    ) -> Dict[str, str]:
        """
        Erstelle Classification-spezifische Plots
        """
        plots = {}
        
        # Confusion Matrix
        fig, axes = plt.subplots(2, 2, figsize=(15, 12))
        
        # Confusion Matrix
        cm = confusion_matrix(y_true, y_pred)
        sns.heatmap(cm, annot=True, fmt='d', ax=axes[0,0], cmap='Blues')
        axes[0,0].set_title(f'{model_name} - Confusion Matrix')
        axes[0,0].set_xlabel('Predicted')
        axes[0,0].set_ylabel('Actual')
        
        # ROC Curve (nur für binary classification)
        if problem_type == 'binary_classification' and y_pred_proba is not None:
            fpr, tpr, _ = roc_curve(y_true, y_pred_proba)
            roc_auc = roc_auc_score(y_true, y_pred_proba)
            
            axes[0,1].plot(fpr, tpr, color='darkorange', lw=2,
                          label=f'ROC curve (AUC = {roc_auc:.2f})')
            axes[0,1].plot([0, 1], [0, 1], color='navy', lw=2, linestyle='--')
            axes[0,1].set_xlim([0.0, 1.0])
            axes[0,1].set_ylim([0.0, 1.05])
            axes[0,1].set_xlabel('False Positive Rate')
            axes[0,1].set_ylabel('True Positive Rate')
            axes[0,1].set_title(f'{model_name} - ROC Curve')
            axes[0,1].legend(loc="lower right")
            
            # Precision-Recall Curve
            precision, recall, _ = precision_recall_curve(y_true, y_pred_proba)
            axes[1,0].plot(recall, precision, color='blue', lw=2)
            axes[1,0].set_xlabel('Recall')
            axes[1,0].set_ylabel('Precision')
            axes[1,0].set_title(f'{model_name} - Precision-Recall Curve')
            
        # Prediction Distribution
        axes[1,1].hist(y_pred_proba if y_pred_proba is not None else y_pred, 
                      bins=20, alpha=0.7, color='green')
        axes[1,1].set_xlabel('Prediction Probability' if y_pred_proba is not None else 'Predictions')
        axes[1,1].set_ylabel('Frequency')
        axes[1,1].set_title(f'{model_name} - Prediction Distribution')
        
        plt.tight_layout()
        plot_path = self.results_dir / f'{model_name}_classification_plots.png'
        plt.savefig(plot_path, dpi=300, bbox_inches='tight')
        plt.close()
        
        plots['classification_analysis'] = str(plot_path)
        
        return plots
    
    def _create_feature_importance_plot(
        self,
        feature_importance: Dict[str, float],
        model_name: str
    ) -> str:
        """
        Erstelle Feature Importance Plot
        """
        # Top 15 Features
        sorted_features = sorted(
            feature_importance.items(),
            key=lambda x: x[1],
            reverse=True
        )[:15]
        
        features, importances = zip(*sorted_features)
        
        plt.figure(figsize=(10, 8))
        plt.barh(range(len(features)), importances)
        plt.yticks(range(len(features)), features)
        plt.xlabel('Feature Importance')
        plt.title(f'{model_name} - Top 15 Feature Importances')
        plt.gca().invert_yaxis()
        
        plot_path = self.results_dir / f'{model_name}_feature_importance.png'
        plt.savefig(plot_path, dpi=300, bbox_inches='tight')
        plt.close()
        
        return str(plot_path)
    
    def compare_models(
        self,
        evaluation_results: List[Dict[str, Any]]
    ) -> Dict[str, Any]:
        """
        Vergleiche multiple Model-Evaluationen
        """
        logger.info(f"Vergleiche {len(evaluation_results)} Modelle")
        
        comparison = {
            'models': [result['model_name'] for result in evaluation_results],
            'comparison_metrics': {}
        }
        
        # Sammle alle verfügbaren Metriken
        all_metrics = set()
        for result in evaluation_results:
            all_metrics.update(result['basic_metrics'].keys())
        
        # Vergleiche Metriken
        for metric in all_metrics:
            metric_values = []
            for result in evaluation_results:
                value = result['basic_metrics'].get(metric)
                metric_values.append(value if value is not None else np.nan)
            
            comparison['comparison_metrics'][metric] = {
                'values': metric_values,
                'best_model_index': int(np.nanargmax(metric_values))
                if metric in ['accuracy', 'roc_auc', 'r2_score', 'f1_macro'] 
                else int(np.nanargmin(metric_values)),
                'best_model': comparison['models'][
                    int(np.nanargmax(metric_values))
                    if metric in ['accuracy', 'roc_auc', 'r2_score', 'f1_macro']
                    else int(np.nanargmin(metric_values))
                ]
            }
        
        # Model Ranking
        ranking_scores = []
        for i, result in enumerate(evaluation_results):
            score = 0
            count = 0
            for metric, comparison_data in comparison['comparison_metrics'].items():
                if not np.isnan(comparison_data['values'][i]):
                    if comparison_data['best_model_index'] == i:
                        score += 1
                    count += 1
            
            ranking_scores.append(score / count if count > 0 else 0)
        
        comparison['model_ranking'] = sorted(
            zip(comparison['models'], ranking_scores),
            key=lambda x: x[1],
            reverse=True
        )
        
        return comparison
    
    def generate_evaluation_report(
        self,
        evaluation_results: Dict[str, Any],
        save_to_file: bool = True
    ) -> str:
        """
        Generiere detaillierten Evaluation Report
        """
        model_name = evaluation_results['model_name']
        problem_type = evaluation_results['problem_type']
        
        report = f"""
# Model Evaluation Report
## Model: {model_name}
## Problem Type: {problem_type.replace('_', ' ').title()}

### Basic Metrics
"""
        
        for metric, value in evaluation_results['basic_metrics'].items():
            report += f"- **{metric.replace('_', ' ').title()}**: {value:.4f}\n"
        
        if evaluation_results.get('basketball_metrics'):
            report += "\n### Basketball-Specific Metrics\n"
            for category, metrics in evaluation_results['basketball_metrics'].items():
                report += f"\n#### {category.replace('_', ' ').title()}\n"
                if isinstance(metrics, dict):
                    for metric, value in metrics.items():
                        report += f"- {metric}: {value}\n"
                else:
                    report += f"- {metrics}\n"
        
        if evaluation_results.get('feature_importance'):
            report += "\n### Top Features\n"
            sorted_features = sorted(
                evaluation_results['feature_importance'].items(),
                key=lambda x: x[1],
                reverse=True
            )[:10]
            
            for feature, importance in sorted_features:
                report += f"- {feature}: {importance:.4f}\n"
        
        if save_to_file:
            report_path = self.results_dir / f'{model_name}_evaluation_report.md'
            with open(report_path, 'w', encoding='utf-8') as f:
                f.write(report)
            logger.info(f"Evaluation report saved to {report_path}")
        
        return report

if __name__ == "__main__":
    # Beispiel Usage
    from sklearn.datasets import make_classification
    from sklearn.ensemble import RandomForestClassifier
    from sklearn.model_selection import train_test_split
    
    config = {'results_dir': 'test_results'}
    evaluator = ModelEvaluator(config)
    
    # Dummy Data
    X, y = make_classification(n_samples=1000, n_features=20, n_classes=2, random_state=42)
    X_train, X_test, y_train, y_test = train_test_split(X, y, test_size=0.2, random_state=42)
    
    # Model trainieren
    model = RandomForestClassifier(random_state=42)
    model.fit(X_train, y_train)
    
    # Evaluation
    X_test_df = pd.DataFrame(X_test, columns=[f'feature_{i}' for i in range(X_test.shape[1])])
    y_test_series = pd.Series(y_test)
    
    results = evaluator.evaluate_model(
        model, X_test_df, y_test_series, 'random_forest_test'
    )
    
    print("Evaluation Results:")
    for metric, value in results['basic_metrics'].items():
        print(f"{metric}: {value:.4f}")
    
    # Report generieren
    report = evaluator.generate_evaluation_report(results)
    print("\nGenerated Report Preview:")
    print(report[:500] + "...")