"""
Hyperparameter Optimization für Basketball Analytics ML-Modelle
Verwendung von Optuna für effiziente Bayesian Optimization
"""

import optuna
import numpy as np
import pandas as pd
import logging
from typing import Dict, Any, Callable, Optional, List
from sklearn.model_selection import cross_val_score, StratifiedKFold
from sklearn.ensemble import RandomForestClassifier, GradientBoostingClassifier
from sklearn.linear_model import LogisticRegression
from sklearn.svm import SVC
from sklearn.naive_bayes import GaussianNB
from sklearn.preprocessing import StandardScaler
import xgboost as xgb
import lightgbm as lgb
from optuna.integration import MLflowCallback
import mlflow
import warnings
warnings.filterwarnings('ignore')

logger = logging.getLogger(__name__)

class HyperparameterOptimizer:
    """
    Hyperparameter Optimization für verschiedene ML-Algorithmen
    """
    
    def __init__(self, config: Dict[str, Any]):
        """
        Initialize Hyperparameter Optimizer
        
        Args:
            config: Konfiguration für Optimization
        """
        self.config = config
        self.n_trials = config.get('n_trials', 100)
        self.cv_folds = config.get('cv_folds', 5)
        self.random_state = config.get('random_state', 42)
        
        # Optuna Storage
        storage_url = config.get('optuna_storage', 'sqlite:///basketball_optuna.db')
        self.storage = optuna.storages.RDBStorage(url=storage_url)
        
        # Parameter Spaces für verschiedene Algorithmen
        self.param_spaces = {
            'random_forest': self._random_forest_space,
            'gradient_boosting': self._gradient_boosting_space,
            'logistic_regression': self._logistic_regression_space,
            'svc': self._svc_space,
            'xgboost': self._xgboost_space,
            'lightgbm': self._lightgbm_space,
            'naive_bayes': self._naive_bayes_space
        }
        
        self.optimization_history = []
        
    def optimize(
        self,
        algorithm_class: type,
        X_train: pd.DataFrame,
        y_train: pd.Series,
        model_type: str,
        cv_folds: int = None,
        n_trials: int = None,
        study_name: Optional[str] = None
    ) -> Dict[str, Any]:
        """
        Optimiere Hyperparameter für einen gegebenen Algorithmus
        
        Args:
            algorithm_class: Sklearn/XGBoost/LightGBM Algorithmus-Klasse
            X_train: Training Features
            y_train: Training Target
            model_type: Typ des Models für Parameter-Space
            cv_folds: Cross-Validation Folds
            n_trials: Anzahl Optimization Trials
            study_name: Name der Optuna Study
            
        Returns:
            Beste gefundene Parameter
        """
        logger.info(f"Starte Hyperparameter Optimization für {model_type}")
        
        cv_folds = cv_folds or self.cv_folds
        n_trials = n_trials or self.n_trials
        study_name = study_name or f"{model_type}_optimization"
        
        # Problem Type bestimmen
        problem_type = self._determine_problem_type(y_train)
        
        # Data für Optimization vorbereiten
        if model_type in ['svc', 'logistic_regression']:
            scaler = StandardScaler()
            X_scaled = scaler.fit_transform(X_train)
            X_opt = pd.DataFrame(X_scaled, columns=X_train.columns, index=X_train.index)
        else:
            X_opt = X_train
        
        # Objective Function definieren
        def objective(trial):
            return self._objective_function(
                trial, algorithm_class, X_opt, y_train, 
                model_type, problem_type, cv_folds
            )
        
        # Optuna Study erstellen
        study = optuna.create_study(
            study_name=study_name,
            direction='maximize',
            storage=self.storage,
            load_if_exists=True
        )
        
        # MLflow Callback
        mlflc = MLflowCallback(
            tracking_uri=mlflow.get_tracking_uri(),
            metric_name='cv_score'
        )
        
        # Optimization durchführen
        logger.info(f"Starte {n_trials} Optimization Trials")
        study.optimize(
            objective,
            n_trials=n_trials,
            callbacks=[mlflc],
            show_progress_bar=True
        )
        
        best_params = study.best_params
        best_score = study.best_value
        
        logger.info(f"Optimization abgeschlossen. Beste Score: {best_score:.4f}")
        logger.info(f"Beste Parameter: {best_params}")
        
        # History Update
        optimization_record = {
            'model_type': model_type,
            'best_score': best_score,
            'best_params': best_params,
            'n_trials': n_trials,
            'study_name': study_name
        }
        self.optimization_history.append(optimization_record)
        
        return best_params
    
    def _objective_function(
        self,
        trial: optuna.Trial,
        algorithm_class: type,
        X_train: pd.DataFrame,
        y_train: pd.Series,
        model_type: str,
        problem_type: str,
        cv_folds: int
    ) -> float:
        """
        Objective Function für Optuna
        """
        try:
            # Parameter für Trial vorschlagen
            if model_type not in self.param_spaces:
                raise ValueError(f"Unbekannter Model-Typ: {model_type}")
            
            params = self.param_spaces[model_type](trial)
            
            # Model mit Trial-Parametern erstellen
            model = algorithm_class(**params)
            
            # Cross-Validation Score
            cv_strategy = StratifiedKFold(n_splits=cv_folds, shuffle=True, random_state=self.random_state)
            
            if problem_type == 'regression':
                scoring = 'r2'
            elif problem_type == 'binary_classification':
                scoring = 'roc_auc'
            else:
                scoring = 'f1_macro'
            
            cv_scores = cross_val_score(
                model, X_train, y_train, 
                cv=cv_strategy, scoring=scoring
            )
            
            return cv_scores.mean()
            
        except Exception as e:
            logger.warning(f"Trial failed: {e}")
            return -np.inf
    
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
    
    def _random_forest_space(self, trial: optuna.Trial) -> Dict[str, Any]:
        """
        Parameter Space für Random Forest
        """
        return {
            'n_estimators': trial.suggest_int('n_estimators', 50, 500),
            'max_depth': trial.suggest_int('max_depth', 3, 20),
            'min_samples_split': trial.suggest_int('min_samples_split', 2, 20),
            'min_samples_leaf': trial.suggest_int('min_samples_leaf', 1, 10),
            'max_features': trial.suggest_categorical('max_features', ['sqrt', 'log2', None]),
            'bootstrap': trial.suggest_categorical('bootstrap', [True, False]),
            'random_state': self.random_state
        }
    
    def _gradient_boosting_space(self, trial: optuna.Trial) -> Dict[str, Any]:
        """
        Parameter Space für Gradient Boosting
        """
        return {
            'n_estimators': trial.suggest_int('n_estimators', 50, 300),
            'learning_rate': trial.suggest_float('learning_rate', 0.01, 0.3, log=True),
            'max_depth': trial.suggest_int('max_depth', 3, 15),
            'min_samples_split': trial.suggest_int('min_samples_split', 2, 20),
            'min_samples_leaf': trial.suggest_int('min_samples_leaf', 1, 10),
            'subsample': trial.suggest_float('subsample', 0.6, 1.0),
            'max_features': trial.suggest_categorical('max_features', ['sqrt', 'log2', None]),
            'random_state': self.random_state
        }
    
    def _logistic_regression_space(self, trial: optuna.Trial) -> Dict[str, Any]:
        """
        Parameter Space für Logistic Regression
        """
        return {
            'C': trial.suggest_float('C', 1e-4, 1e2, log=True),
            'penalty': trial.suggest_categorical('penalty', ['l1', 'l2', 'elasticnet']),
            'solver': trial.suggest_categorical('solver', ['liblinear', 'saga']),
            'l1_ratio': trial.suggest_float('l1_ratio', 0.0, 1.0) if trial.params.get('penalty') == 'elasticnet' else None,
            'max_iter': 1000,
            'random_state': self.random_state
        }
    
    def _svc_space(self, trial: optuna.Trial) -> Dict[str, Any]:
        """
        Parameter Space für SVM
        """
        kernel = trial.suggest_categorical('kernel', ['rbf', 'poly', 'sigmoid'])
        
        params = {
            'C': trial.suggest_float('C', 1e-3, 1e3, log=True),
            'kernel': kernel,
            'gamma': trial.suggest_categorical('gamma', ['scale', 'auto']),
            'random_state': self.random_state
        }
        
        if kernel == 'poly':
            params['degree'] = trial.suggest_int('degree', 2, 5)
        
        return params
    
    def _xgboost_space(self, trial: optuna.Trial) -> Dict[str, Any]:
        """
        Parameter Space für XGBoost
        """
        return {
            'n_estimators': trial.suggest_int('n_estimators', 50, 500),
            'max_depth': trial.suggest_int('max_depth', 3, 15),
            'learning_rate': trial.suggest_float('learning_rate', 0.01, 0.3, log=True),
            'subsample': trial.suggest_float('subsample', 0.6, 1.0),
            'colsample_bytree': trial.suggest_float('colsample_bytree', 0.6, 1.0),
            'reg_alpha': trial.suggest_float('reg_alpha', 1e-8, 1.0, log=True),
            'reg_lambda': trial.suggest_float('reg_lambda', 1e-8, 1.0, log=True),
            'min_child_weight': trial.suggest_int('min_child_weight', 1, 10),
            'random_state': self.random_state
        }
    
    def _lightgbm_space(self, trial: optuna.Trial) -> Dict[str, Any]:
        """
        Parameter Space für LightGBM
        """
        return {
            'n_estimators': trial.suggest_int('n_estimators', 50, 500),
            'max_depth': trial.suggest_int('max_depth', 3, 15),
            'learning_rate': trial.suggest_float('learning_rate', 0.01, 0.3, log=True),
            'num_leaves': trial.suggest_int('num_leaves', 10, 300),
            'subsample': trial.suggest_float('subsample', 0.6, 1.0),
            'colsample_bytree': trial.suggest_float('colsample_bytree', 0.6, 1.0),
            'reg_alpha': trial.suggest_float('reg_alpha', 1e-8, 1.0, log=True),
            'reg_lambda': trial.suggest_float('reg_lambda', 1e-8, 1.0, log=True),
            'min_child_weight': trial.suggest_float('min_child_weight', 1e-5, 100, log=True),
            'random_state': self.random_state,
            'verbosity': -1
        }
    
    def _naive_bayes_space(self, trial: optuna.Trial) -> Dict[str, Any]:
        """
        Parameter Space für Naive Bayes (minimale Parameter)
        """
        return {
            'var_smoothing': trial.suggest_float('var_smoothing', 1e-10, 1e-6, log=True)
        }
    
    def get_optimization_results(self, study_name: str) -> Dict[str, Any]:
        """
        Hole Optimization-Ergebnisse für eine Study
        """
        try:
            study = optuna.load_study(
                study_name=study_name,
                storage=self.storage
            )
            
            return {
                'study_name': study_name,
                'best_value': study.best_value,
                'best_params': study.best_params,
                'n_trials': len(study.trials),
                'best_trial': study.best_trial.number,
                'study_direction': study.direction.name
            }
            
        except Exception as e:
            logger.error(f"Fehler beim Laden der Study {study_name}: {e}")
            return {'error': str(e)}
    
    def get_all_studies(self) -> List[str]:
        """
        Hole alle verfügbaren Study-Namen
        """
        try:
            return optuna.study.get_all_study_names(storage=self.storage)
        except Exception as e:
            logger.error(f"Fehler beim Laden der Studies: {e}")
            return []
    
    def delete_study(self, study_name: str) -> bool:
        """
        Lösche eine Study
        """
        try:
            optuna.delete_study(study_name=study_name, storage=self.storage)
            logger.info(f"Study {study_name} gelöscht")
            return True
        except Exception as e:
            logger.error(f"Fehler beim Löschen der Study {study_name}: {e}")
            return False
    
    def create_optimization_report(self) -> Dict[str, Any]:
        """
        Erstelle Optimization Report
        """
        report = {
            'optimization_history': self.optimization_history,
            'available_studies': self.get_all_studies(),
            'total_optimizations': len(self.optimization_history)
        }
        
        if self.optimization_history:
            best_optimization = max(
                self.optimization_history,
                key=lambda x: x['best_score']
            )
            report['best_optimization'] = best_optimization
            
            # Average scores per model type
            model_scores = {}
            for opt in self.optimization_history:
                model_type = opt['model_type']
                if model_type not in model_scores:
                    model_scores[model_type] = []
                model_scores[model_type].append(opt['best_score'])
            
            model_averages = {
                model: np.mean(scores)
                for model, scores in model_scores.items()
            }
            report['model_type_averages'] = model_averages
        
        return report
    
    def visualize_optimization(self, study_name: str) -> Optional[Any]:
        """
        Erstelle Visualisierungen für eine Study
        """
        try:
            study = optuna.load_study(
                study_name=study_name,
                storage=self.storage
            )
            
            # Optuna built-in visualizations
            import optuna.visualization as vis
            
            visualizations = {
                'optimization_history': vis.plot_optimization_history(study),
                'param_importances': vis.plot_param_importances(study),
                'slice': vis.plot_slice(study),
                'parallel_coordinate': vis.plot_parallel_coordinate(study)
            }
            
            return visualizations
            
        except Exception as e:
            logger.error(f"Fehler bei Visualisierung von {study_name}: {e}")
            return None

# Basketball-spezifische Optimization Utilities
class BasketballOptimizer(HyperparameterOptimizer):
    """
    Basketball-spezifische Hyperparameter Optimization
    """
    
    def __init__(self, config: Dict[str, Any]):
        super().__init__(config)
        
        # Basketball-spezifische Parameter Spaces
        self.basketball_param_spaces = {
            'shot_prediction': self._shot_prediction_space,
            'player_performance': self._player_performance_space,
            'game_outcome': self._game_outcome_space,
            'injury_risk': self._injury_risk_space
        }
    
    def _shot_prediction_space(self, trial: optuna.Trial, base_space: Dict) -> Dict[str, Any]:
        """
        Basketball Shot Prediction spezifische Parameter
        """
        params = base_space.copy()
        
        # Zusätzliche Features für Shot Prediction
        if 'n_estimators' in params:
            # Mehr Estimators für komplexe Shot Patterns
            params['n_estimators'] = trial.suggest_int('n_estimators', 100, 800)
        
        if 'max_depth' in params:
            # Tiefere Bäume für räumliche Shot-Patterns
            params['max_depth'] = trial.suggest_int('max_depth', 5, 25)
        
        return params
    
    def _player_performance_space(self, trial: optuna.Trial, base_space: Dict) -> Dict[str, Any]:
        """
        Player Performance Prediction spezifische Parameter
        """
        params = base_space.copy()
        
        # Regularization ist wichtig für Player Performance
        if 'reg_alpha' in params:
            params['reg_alpha'] = trial.suggest_float('reg_alpha', 1e-5, 10.0, log=True)
        
        if 'reg_lambda' in params:
            params['reg_lambda'] = trial.suggest_float('reg_lambda', 1e-5, 10.0, log=True)
        
        return params
    
    def _game_outcome_space(self, trial: optuna.Trial, base_space: Dict) -> Dict[str, Any]:
        """
        Game Outcome Prediction spezifische Parameter
        """
        params = base_space.copy()
        
        # Balanced Trees für Game Outcome
        if 'min_samples_leaf' in params:
            params['min_samples_leaf'] = trial.suggest_int('min_samples_leaf', 5, 20)
        
        return params
    
    def _injury_risk_space(self, trial: optuna.Trial, base_space: Dict) -> Dict[str, Any]:
        """
        Injury Risk Prediction spezifische Parameter
        """
        params = base_space.copy()
        
        # Konservative Parameter für Injury Risk (wichtige Anwendung)
        if 'learning_rate' in params:
            params['learning_rate'] = trial.suggest_float('learning_rate', 0.01, 0.1)
        
        if 'subsample' in params:
            params['subsample'] = trial.suggest_float('subsample', 0.7, 0.95)
        
        return params
    
    def optimize_for_basketball_task(
        self,
        algorithm_class: type,
        X_train: pd.DataFrame,
        y_train: pd.Series,
        model_type: str,
        basketball_task: str,
        **kwargs
    ) -> Dict[str, Any]:
        """
        Optimiere für spezifische Basketball-Tasks
        """
        logger.info(f"Basketball-spezifische Optimization für {basketball_task}")
        
        # Erweitere Parameter Space für Basketball Task
        original_space = self.param_spaces.get(model_type)
        if original_space and basketball_task in self.basketball_param_spaces:
            def enhanced_space(trial):
                base_params = original_space(trial)
                return self.basketball_param_spaces[basketball_task](trial, base_params)
            
            self.param_spaces[f"{model_type}_{basketball_task}"] = enhanced_space
            model_type_enhanced = f"{model_type}_{basketball_task}"
        else:
            model_type_enhanced = model_type
        
        return self.optimize(
            algorithm_class, X_train, y_train, 
            model_type_enhanced, **kwargs
        )

if __name__ == "__main__":
    # Beispiel Usage
    from sklearn.datasets import make_classification
    
    config = {
        'n_trials': 50,
        'cv_folds': 3,
        'random_state': 42,
        'optuna_storage': 'sqlite:///test_optuna.db'
    }
    
    optimizer = HyperparameterOptimizer(config)
    
    # Dummy Data
    X, y = make_classification(n_samples=1000, n_features=20, n_classes=2, random_state=42)
    X_df = pd.DataFrame(X, columns=[f'feature_{i}' for i in range(X.shape[1])])
    y_series = pd.Series(y)
    
    # Optimization
    best_params = optimizer.optimize(
        RandomForestClassifier,
        X_df,
        y_series,
        'random_forest',
        n_trials=20
    )
    
    print(f"Beste Parameter: {best_params}")
    
    # Report
    report = optimizer.create_optimization_report()
    print(f"Optimization Report: {report}")