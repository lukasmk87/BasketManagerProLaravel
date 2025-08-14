"""
Advanced ML Training Pipeline für Basketball Analytics
Automatisiertes Training von ML-Modellen mit AutoML-Capabilities
"""

import pandas as pd
import numpy as np
import joblib
import logging
from typing import Dict, List, Tuple, Optional, Any
from pathlib import Path
from datetime import datetime
import optuna
from optuna.integration import MLflowCallback
import mlflow
import mlflow.sklearn
from sklearn.model_selection import train_test_split, cross_val_score, StratifiedKFold
from sklearn.metrics import accuracy_score, precision_score, recall_score, f1_score, roc_auc_score
from sklearn.preprocessing import StandardScaler, LabelEncoder
from sklearn.ensemble import RandomForestClassifier, GradientBoostingClassifier
from sklearn.linear_model import LogisticRegression
from sklearn.svm import SVC
from sklearn.naive_bayes import GaussianNB
import xgboost as xgb
import lightgbm as lgb
from feature_selector import FeatureSelector
from hyperparameter_optimizer import HyperparameterOptimizer
from model_evaluator import ModelEvaluator

# Logging Setup
logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(name)s - %(levelname)s - %(message)s'
)
logger = logging.getLogger(__name__)

class MLTrainer:
    """
    Hauptklasse für automatisiertes ML Model Training
    """
    
    def __init__(self, config: Dict[str, Any]):
        """
        Initialize ML Trainer
        
        Args:
            config: Konfiguration für Training-Pipeline
        """
        self.config = config
        self.models_dir = Path(config.get('models_dir', 'models'))
        self.models_dir.mkdir(exist_ok=True)
        
        # MLflow Setup
        mlflow.set_tracking_uri(config.get('mlflow_uri', 'sqlite:///mlflow.db'))
        mlflow.set_experiment(config.get('experiment_name', 'BasketballAI'))
        
        # Verfügbare Algorithmen
        self.algorithms = {
            'random_forest': RandomForestClassifier,
            'gradient_boosting': GradientBoostingClassifier,
            'logistic_regression': LogisticRegression,
            'svc': SVC,
            'naive_bayes': GaussianNB,
            'xgboost': xgb.XGBClassifier,
            'lightgbm': lgb.LGBMClassifier
        }
        
        # Feature Selector und Optimizer
        self.feature_selector = FeatureSelector(config)
        self.hyperparameter_optimizer = HyperparameterOptimizer(config)
        self.model_evaluator = ModelEvaluator(config)
        
        # Training History
        self.training_history = []
        
    def train_model(
        self, 
        data: pd.DataFrame, 
        target_column: str,
        model_type: str = 'auto',
        use_auto_features: bool = True,
        use_hyperopt: bool = True,
        cv_folds: int = 5
    ) -> Dict[str, Any]:
        """
        Trainiere ein ML-Model mit automatischer Optimierung
        
        Args:
            data: Training data
            target_column: Name der Ziel-Variable
            model_type: Typ des Models ('auto' für AutoML)
            use_auto_features: Verwende automatische Feature-Selection
            use_hyperopt: Verwende Hyperparameter-Optimierung
            cv_folds: Cross-Validation Folds
            
        Returns:
            Training-Ergebnisse
        """
        logger.info(f"Starte Training für {model_type} Model")
        
        with mlflow.start_run():
            # Log Parameters
            mlflow.log_param("model_type", model_type)
            mlflow.log_param("use_auto_features", use_auto_features)
            mlflow.log_param("use_hyperopt", use_hyperopt)
            mlflow.log_param("cv_folds", cv_folds)
            
            # Data Preprocessing
            X, y = self._preprocess_data(data, target_column)
            
            # Feature Selection
            if use_auto_features:
                X = self.feature_selector.select_features(X, y)
                mlflow.log_param("selected_features", X.columns.tolist())
            
            # Train/Test Split
            X_train, X_test, y_train, y_test = train_test_split(
                X, y, test_size=0.2, random_state=42, stratify=y
            )
            
            # Model Selection & Training
            if model_type == 'auto':
                best_model, best_score = self._auto_ml_selection(X_train, y_train, cv_folds)
            else:
                best_model, best_score = self._train_single_model(
                    model_type, X_train, y_train, use_hyperopt, cv_folds
                )
            
            # Model Evaluation
            evaluation_results = self.model_evaluator.evaluate_model(
                best_model, X_test, y_test
            )
            
            # Log Metrics
            for metric, value in evaluation_results.items():
                mlflow.log_metric(metric, value)
            
            # Model Persistence
            model_path = self._save_model(best_model, model_type, evaluation_results)
            mlflow.log_artifact(str(model_path))
            
            # Training History Update
            training_record = {
                'timestamp': datetime.now(),
                'model_type': model_type,
                'cv_score': best_score,
                'test_metrics': evaluation_results,
                'model_path': model_path,
                'feature_count': len(X.columns)
            }
            self.training_history.append(training_record)
            
            logger.info(f"Training abgeschlossen. CV-Score: {best_score:.4f}")
            
            return {
                'model': best_model,
                'cv_score': best_score,
                'test_metrics': evaluation_results,
                'model_path': model_path,
                'training_record': training_record
            }
    
    def _preprocess_data(self, data: pd.DataFrame, target_column: str) -> Tuple[pd.DataFrame, pd.Series]:
        """
        Preprocessing der Trainingsdaten
        
        Args:
            data: Rohdaten
            target_column: Ziel-Variable
            
        Returns:
            Features und Target
        """
        logger.info("Starte Data Preprocessing")
        
        # Kopie der Daten
        df = data.copy()
        
        # Missing Values behandeln
        df = self._handle_missing_values(df)
        
        # Categorical Features enkodieren
        df = self._encode_categorical_features(df)
        
        # Feature Engineering
        df = self._engineer_features(df)
        
        # Features und Target trennen
        X = df.drop(columns=[target_column])
        y = df[target_column]
        
        # Target encoding falls nötig
        if y.dtype == 'object':
            le = LabelEncoder()
            y = le.fit_transform(y)
        
        logger.info(f"Preprocessing abgeschlossen. Features: {X.shape[1]}, Samples: {X.shape[0]}")
        
        return X, y
    
    def _handle_missing_values(self, df: pd.DataFrame) -> pd.DataFrame:
        """
        Behandle Missing Values intelligent
        """
        # Numerische Spalten: Median
        numeric_cols = df.select_dtypes(include=[np.number]).columns
        df[numeric_cols] = df[numeric_cols].fillna(df[numeric_cols].median())
        
        # Kategorische Spalten: Mode
        categorical_cols = df.select_dtypes(include=['object']).columns
        for col in categorical_cols:
            df[col] = df[col].fillna(df[col].mode().iloc[0] if not df[col].mode().empty else 'Unknown')
        
        return df
    
    def _encode_categorical_features(self, df: pd.DataFrame) -> pd.DataFrame:
        """
        Enkodiere kategorische Features
        """
        categorical_cols = df.select_dtypes(include=['object']).columns
        
        for col in categorical_cols:
            # Target encoding für High-Cardinality Features
            if df[col].nunique() > 10:
                # Frequency encoding
                freq_encoding = df[col].value_counts(normalize=True).to_dict()
                df[f'{col}_freq'] = df[col].map(freq_encoding)
                df = df.drop(columns=[col])
            else:
                # One-hot encoding für Low-Cardinality
                dummies = pd.get_dummies(df[col], prefix=col, drop_first=True)
                df = pd.concat([df, dummies], axis=1)
                df = df.drop(columns=[col])
        
        return df
    
    def _engineer_features(self, df: pd.DataFrame) -> pd.DataFrame:
        """
        Basketball-spezifisches Feature Engineering
        """
        # Shooting Efficiency Features
        if 'shots_made' in df.columns and 'shots_attempted' in df.columns:
            df['shooting_percentage'] = np.where(
                df['shots_attempted'] > 0,
                df['shots_made'] / df['shots_attempted'],
                0
            )
        
        # Performance Ratios
        if 'points' in df.columns and 'minutes_played' in df.columns:
            df['points_per_minute'] = np.where(
                df['minutes_played'] > 0,
                df['points'] / df['minutes_played'],
                0
            )
        
        # Defensive Metrics
        if 'steals' in df.columns and 'blocks' in df.columns:
            df['defensive_actions'] = df['steals'] + df['blocks']
        
        # Efficiency Metrics
        if all(col in df.columns for col in ['rebounds', 'assists', 'turnovers']):
            df['efficiency'] = (df['rebounds'] + df['assists']) / np.maximum(df['turnovers'], 1)
        
        return df
    
    def _auto_ml_selection(
        self, 
        X_train: pd.DataFrame, 
        y_train: pd.Series, 
        cv_folds: int
    ) -> Tuple[Any, float]:
        """
        Automatische Model-Auswahl mit Cross-Validation
        """
        logger.info("Starte AutoML Model Selection")
        
        best_model = None
        best_score = -np.inf
        model_scores = {}
        
        # Standard Scaler für manche Algorithmen
        scaler = StandardScaler()
        X_scaled = scaler.fit_transform(X_train)
        
        for name, algorithm in self.algorithms.items():
            try:
                logger.info(f"Teste {name}")
                
                # Model mit Default-Parametern
                if name in ['svc', 'logistic_regression']:
                    model = algorithm(random_state=42, max_iter=1000)
                    X_cv = X_scaled
                else:
                    model = algorithm(random_state=42)
                    X_cv = X_train
                
                # Cross Validation
                cv = StratifiedKFold(n_splits=cv_folds, shuffle=True, random_state=42)
                scores = cross_val_score(model, X_cv, y_train, cv=cv, scoring='roc_auc')
                
                mean_score = scores.mean()
                model_scores[name] = mean_score
                
                logger.info(f"{name}: {mean_score:.4f} (+/- {scores.std() * 2:.4f})")
                
                if mean_score > best_score:
                    best_score = mean_score
                    best_model = model
                    
            except Exception as e:
                logger.warning(f"Fehler bei {name}: {e}")
                continue
        
        # Best Model trainieren
        if best_model is not None:
            if any(isinstance(best_model, alg) for alg in [SVC, LogisticRegression]):
                best_model.fit(X_scaled, y_train)
            else:
                best_model.fit(X_train, y_train)
        
        # Log Model Scores
        mlflow.log_dict(model_scores, "model_comparison.json")
        
        logger.info(f"Bestes Model: {type(best_model).__name__} (Score: {best_score:.4f})")
        
        return best_model, best_score
    
    def _train_single_model(
        self,
        model_type: str,
        X_train: pd.DataFrame,
        y_train: pd.Series,
        use_hyperopt: bool,
        cv_folds: int
    ) -> Tuple[Any, float]:
        """
        Trainiere ein einzelnes Model
        """
        logger.info(f"Trainiere {model_type} Model")
        
        if model_type not in self.algorithms:
            raise ValueError(f"Unbekannter Model-Typ: {model_type}")
        
        algorithm = self.algorithms[model_type]
        
        if use_hyperopt:
            # Hyperparameter Optimierung
            best_params = self.hyperparameter_optimizer.optimize(
                algorithm, X_train, y_train, model_type, cv_folds
            )
            model = algorithm(**best_params)
        else:
            # Default Parameter
            model = algorithm(random_state=42)
        
        # Model trainieren
        if model_type in ['svc', 'logistic_regression']:
            scaler = StandardScaler()
            X_scaled = scaler.fit_transform(X_train)
            model.fit(X_scaled, y_train)
        else:
            model.fit(X_train, y_train)
        
        # Cross Validation Score
        cv = StratifiedKFold(n_splits=cv_folds, shuffle=True, random_state=42)
        scores = cross_val_score(model, X_train, y_train, cv=cv, scoring='roc_auc')
        cv_score = scores.mean()
        
        return model, cv_score
    
    def _save_model(
        self, 
        model: Any, 
        model_type: str, 
        metrics: Dict[str, float]
    ) -> Path:
        """
        Speichere trainiertes Model
        """
        timestamp = datetime.now().strftime('%Y%m%d_%H%M%S')
        filename = f"{model_type}_{timestamp}.pkl"
        model_path = self.models_dir / filename
        
        # Model und Metadata speichern
        model_data = {
            'model': model,
            'model_type': model_type,
            'metrics': metrics,
            'timestamp': timestamp,
            'version': '1.0'
        }
        
        joblib.dump(model_data, model_path)
        logger.info(f"Model gespeichert: {model_path}")
        
        return model_path
    
    def batch_training(
        self, 
        datasets: List[Dict[str, Any]],
        parallel: bool = True
    ) -> List[Dict[str, Any]]:
        """
        Batch Training für multiple Datasets/Models
        
        Args:
            datasets: Liste von Dataset-Configs
            parallel: Parallele Ausführung
            
        Returns:
            Training-Ergebnisse
        """
        logger.info(f"Starte Batch Training für {len(datasets)} Datasets")
        
        results = []
        
        for i, dataset_config in enumerate(datasets):
            logger.info(f"Training Dataset {i+1}/{len(datasets)}")
            
            try:
                result = self.train_model(**dataset_config)
                results.append(result)
            except Exception as e:
                logger.error(f"Fehler bei Dataset {i+1}: {e}")
                results.append({'error': str(e)})
        
        logger.info(f"Batch Training abgeschlossen. {len(results)} Modelle trainiert")
        
        return results
    
    def get_training_history(self) -> List[Dict[str, Any]]:
        """
        Gebe Training History zurück
        """
        return self.training_history
    
    def load_model(self, model_path: str) -> Dict[str, Any]:
        """
        Lade ein gespeichertes Model
        """
        model_data = joblib.load(model_path)
        logger.info(f"Model geladen: {model_path}")
        return model_data

# Beispiel-Config
DEFAULT_CONFIG = {
    'models_dir': 'models',
    'mlflow_uri': 'sqlite:///basketball_mlflow.db',
    'experiment_name': 'BasketballAI_Training',
    'optuna_storage': 'sqlite:///basketball_optuna.db',
    'n_trials': 100,
    'cv_folds': 5,
    'test_size': 0.2,
    'random_state': 42
}

if __name__ == "__main__":
    # Beispiel-Usage
    config = DEFAULT_CONFIG
    trainer = MLTrainer(config)
    
    # Dummy-Daten für Testing
    np.random.seed(42)
    data = pd.DataFrame({
        'points': np.random.randint(0, 50, 1000),
        'rebounds': np.random.randint(0, 20, 1000),
        'assists': np.random.randint(0, 15, 1000),
        'minutes_played': np.random.randint(10, 48, 1000),
        'shots_made': np.random.randint(0, 25, 1000),
        'shots_attempted': np.random.randint(0, 40, 1000),
        'position': np.random.choice(['PG', 'SG', 'SF', 'PF', 'C'], 1000),
        'win': np.random.choice([0, 1], 1000)
    })
    
    # Training starten
    result = trainer.train_model(
        data=data,
        target_column='win',
        model_type='auto',
        use_auto_features=True,
        use_hyperopt=True
    )
    
    print(f"Training abgeschlossen. CV-Score: {result['cv_score']:.4f}")
    print(f"Test-Metriken: {result['test_metrics']}")