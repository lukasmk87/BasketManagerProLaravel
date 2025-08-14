"""
Automatische Feature Selection für Basketball Analytics
Intelligente Auswahl der relevantesten Features für ML-Modelle
"""

import pandas as pd
import numpy as np
import logging
from typing import Dict, List, Tuple, Any, Optional
from sklearn.feature_selection import (
    SelectKBest, f_classif, f_regression, mutual_info_classif, 
    mutual_info_regression, RFE, RFECV, SelectFromModel
)
from sklearn.ensemble import RandomForestClassifier, RandomForestRegressor
from sklearn.linear_model import LassoCV, ElasticNetCV
from sklearn.preprocessing import StandardScaler
from scipy import stats
import warnings
warnings.filterwarnings('ignore')

logger = logging.getLogger(__name__)

class FeatureSelector:
    """
    Automatische Feature Selection für Basketball-ML-Models
    """
    
    def __init__(self, config: Dict[str, Any]):
        """
        Initialize Feature Selector
        
        Args:
            config: Konfiguration für Feature Selection
        """
        self.config = config
        self.selection_methods = {
            'statistical': self._statistical_selection,
            'recursive': self._recursive_selection,
            'model_based': self._model_based_selection,
            'mutual_info': self._mutual_info_selection,
            'correlation': self._correlation_selection,
            'variance': self._variance_selection
        }
        
        self.selected_features = None
        self.feature_scores = {}
        self.selection_history = []
        
    def select_features(
        self,
        X: pd.DataFrame,
        y: pd.Series,
        method: str = 'auto',
        k_features: Optional[int] = None,
        threshold: float = 0.01
    ) -> pd.DataFrame:
        """
        Hauptmethode für Feature Selection
        
        Args:
            X: Feature Matrix
            y: Target Variable
            method: Selection-Methode ('auto', 'statistical', etc.)
            k_features: Anzahl gewünschter Features
            threshold: Threshold für Feature-Wichtigkeit
            
        Returns:
            Gefilterte Feature Matrix
        """
        logger.info(f"Starte Feature Selection mit {method} method")
        logger.info(f"Original Features: {X.shape[1]}")
        
        # Problem-Type bestimmen
        problem_type = self._determine_problem_type(y)
        
        if method == 'auto':
            # Automatische Methoden-Auswahl
            X_selected = self._auto_selection(X, y, problem_type, k_features, threshold)
        else:
            # Spezifische Methode
            if method not in self.selection_methods:
                raise ValueError(f"Unbekannte Methode: {method}")
            
            X_selected = self.selection_methods[method](X, y, problem_type, k_features, threshold)
        
        self.selected_features = X_selected.columns.tolist()
        
        logger.info(f"Feature Selection abgeschlossen. Ausgewählte Features: {X_selected.shape[1]}")
        
        # History Update
        selection_record = {
            'method': method,
            'original_features': X.shape[1],
            'selected_features': X_selected.shape[1],
            'features': self.selected_features,
            'problem_type': problem_type
        }
        self.selection_history.append(selection_record)
        
        return X_selected
    
    def _determine_problem_type(self, y: pd.Series) -> str:
        """
        Bestimme Problem-Type (Classification vs Regression)
        """
        unique_values = y.nunique()
        
        if unique_values == 2:
            return 'binary_classification'
        elif unique_values <= 10 and y.dtype in ['int64', 'object']:
            return 'multiclass_classification'
        else:
            return 'regression'
    
    def _auto_selection(
        self,
        X: pd.DataFrame,
        y: pd.Series,
        problem_type: str,
        k_features: Optional[int],
        threshold: float
    ) -> pd.DataFrame:
        """
        Automatische Feature Selection mit Ensemble-Ansatz
        """
        logger.info("Verwende Auto-Selection mit Ensemble-Ansatz")
        
        # Verschiedene Methoden anwenden
        methods_results = {}
        
        for method_name in ['statistical', 'recursive', 'model_based', 'mutual_info']:
            try:
                X_method = self.selection_methods[method_name](
                    X, y, problem_type, k_features, threshold
                )
                methods_results[method_name] = set(X_method.columns)
            except Exception as e:
                logger.warning(f"Fehler bei {method_name}: {e}")
                continue
        
        # Ensemble Voting
        feature_votes = {}
        for features_set in methods_results.values():
            for feature in features_set:
                feature_votes[feature] = feature_votes.get(feature, 0) + 1
        
        # Features nach Votes sortieren
        sorted_features = sorted(
            feature_votes.items(),
            key=lambda x: x[1],
            reverse=True
        )
        
        # Top Features auswählen
        if k_features:
            selected_features = [f[0] for f in sorted_features[:k_features]]
        else:
            # Features mit mindestens 2 Votes
            min_votes = max(2, len(methods_results) // 2)
            selected_features = [f[0] for f in sorted_features if f[1] >= min_votes]
        
        self.feature_scores['ensemble_votes'] = dict(sorted_features)
        
        logger.info(f"Ensemble Selection: {len(selected_features)} Features ausgewählt")
        
        return X[selected_features]
    
    def _statistical_selection(
        self,
        X: pd.DataFrame,
        y: pd.Series,
        problem_type: str,
        k_features: Optional[int],
        threshold: float
    ) -> pd.DataFrame:
        """
        Statistische Feature Selection (ANOVA F-test, etc.)
        """
        if problem_type in ['binary_classification', 'multiclass_classification']:
            score_func = f_classif
        else:
            score_func = f_regression
        
        if k_features:
            selector = SelectKBest(score_func=score_func, k=k_features)
        else:
            # Top 50% Features
            k_features = max(1, int(X.shape[1] * 0.5))
            selector = SelectKBest(score_func=score_func, k=k_features)
        
        X_selected = selector.fit_transform(X, y)
        selected_features = X.columns[selector.get_support()].tolist()
        
        # Scores speichern
        feature_scores = dict(zip(X.columns, selector.scores_))
        self.feature_scores['statistical'] = feature_scores
        
        return pd.DataFrame(X_selected, columns=selected_features, index=X.index)
    
    def _recursive_selection(
        self,
        X: pd.DataFrame,
        y: pd.Series,
        problem_type: str,
        k_features: Optional[int],
        threshold: float
    ) -> pd.DataFrame:
        """
        Recursive Feature Elimination
        """
        if problem_type in ['binary_classification', 'multiclass_classification']:
            estimator = RandomForestClassifier(n_estimators=50, random_state=42)
        else:
            estimator = RandomForestRegressor(n_estimators=50, random_state=42)
        
        if k_features:
            selector = RFE(estimator=estimator, n_features_to_select=k_features)
        else:
            # Cross-Validation RFE
            selector = RFECV(estimator=estimator, cv=3, min_features_to_select=1)
        
        selector.fit(X, y)
        selected_features = X.columns[selector.support_].tolist()
        
        # Rankings speichern
        feature_rankings = dict(zip(X.columns, selector.ranking_))
        self.feature_scores['recursive'] = feature_rankings
        
        return X[selected_features]
    
    def _model_based_selection(
        self,
        X: pd.DataFrame,
        y: pd.Series,
        problem_type: str,
        k_features: Optional[int],
        threshold: float
    ) -> pd.DataFrame:
        """
        Model-based Feature Selection (Lasso, Random Forest)
        """
        if problem_type in ['binary_classification', 'multiclass_classification']:
            # Random Forest Feature Importance
            estimator = RandomForestClassifier(n_estimators=100, random_state=42)
            estimator.fit(X, y)
            
            feature_importances = dict(zip(X.columns, estimator.feature_importances_))
            
        else:
            # Lasso Regression für Continuous Target
            estimator = LassoCV(cv=3, random_state=42, max_iter=1000)
            estimator.fit(X, y)
            
            feature_importances = dict(zip(X.columns, np.abs(estimator.coef_)))
        
        # Top Features auswählen
        sorted_features = sorted(
            feature_importances.items(),
            key=lambda x: x[1],
            reverse=True
        )
        
        if k_features:
            selected_features = [f[0] for f in sorted_features[:k_features]]
        else:
            # Features über Threshold
            mean_importance = np.mean(list(feature_importances.values()))
            selected_features = [
                f[0] for f in sorted_features 
                if f[1] > max(threshold, mean_importance * 0.1)
            ]
        
        self.feature_scores['model_based'] = feature_importances
        
        return X[selected_features]
    
    def _mutual_info_selection(
        self,
        X: pd.DataFrame,
        y: pd.Series,
        problem_type: str,
        k_features: Optional[int],
        threshold: float
    ) -> pd.DataFrame:
        """
        Mutual Information Feature Selection
        """
        if problem_type in ['binary_classification', 'multiclass_classification']:
            mi_scores = mutual_info_classif(X, y, random_state=42)
        else:
            mi_scores = mutual_info_regression(X, y, random_state=42)
        
        feature_scores = dict(zip(X.columns, mi_scores))
        
        # Top Features auswählen
        sorted_features = sorted(
            feature_scores.items(),
            key=lambda x: x[1],
            reverse=True
        )
        
        if k_features:
            selected_features = [f[0] for f in sorted_features[:k_features]]
        else:
            # Features über Threshold
            mean_score = np.mean(mi_scores)
            selected_features = [
                f[0] for f in sorted_features 
                if f[1] > max(threshold, mean_score * 0.1)
            ]
        
        self.feature_scores['mutual_info'] = feature_scores
        
        return X[selected_features]
    
    def _correlation_selection(
        self,
        X: pd.DataFrame,
        y: pd.Series,
        problem_type: str,
        k_features: Optional[int],
        threshold: float
    ) -> pd.DataFrame:
        """
        Korrelations-basierte Feature Selection
        """
        # Korrelation mit Target
        correlations = X.corrwith(y).abs()
        
        # Multicollinearity entfernen
        X_filtered = self._remove_multicollinear_features(X, correlations, threshold=0.8)
        
        # Top korrelierte Features
        filtered_correlations = X_filtered.corrwith(y).abs()
        sorted_features = filtered_correlations.sort_values(ascending=False)
        
        if k_features:
            selected_features = sorted_features.head(k_features).index.tolist()
        else:
            # Features über Threshold
            selected_features = sorted_features[sorted_features > threshold].index.tolist()
        
        self.feature_scores['correlation'] = correlations.to_dict()
        
        return X[selected_features]
    
    def _variance_selection(
        self,
        X: pd.DataFrame,
        y: pd.Series,
        problem_type: str,
        k_features: Optional[int],
        threshold: float
    ) -> pd.DataFrame:
        """
        Variance-basierte Feature Selection
        """
        # Low-Variance Features entfernen
        variances = X.var()
        high_variance_features = variances[variances > threshold].index.tolist()
        
        X_high_var = X[high_variance_features]
        
        if k_features and len(high_variance_features) > k_features:
            # Zusätzliche Selection nach Korrelation mit Target
            correlations = X_high_var.corrwith(y).abs()
            top_features = correlations.nlargest(k_features).index.tolist()
            return X[top_features]
        
        self.feature_scores['variance'] = variances.to_dict()
        
        return X_high_var
    
    def _remove_multicollinear_features(
        self,
        X: pd.DataFrame,
        target_correlations: pd.Series,
        threshold: float = 0.8
    ) -> pd.DataFrame:
        """
        Entferne multikollineare Features
        """
        correlation_matrix = X.corr().abs()
        
        # Features nach Target-Korrelation sortieren
        sorted_features = target_correlations.sort_values(ascending=False).index.tolist()
        
        selected_features = []
        
        for feature in sorted_features:
            # Prüfe Korrelation mit bereits ausgewählten Features
            is_correlated = False
            
            for selected in selected_features:
                if correlation_matrix.loc[feature, selected] > threshold:
                    is_correlated = True
                    break
            
            if not is_correlated:
                selected_features.append(feature)
        
        return X[selected_features]
    
    def get_feature_importance_report(self) -> Dict[str, Any]:
        """
        Erstelle Feature Importance Report
        """
        if not self.feature_scores:
            return {"error": "Keine Feature Scores verfügbar"}
        
        report = {
            'selected_features': self.selected_features,
            'feature_scores': self.feature_scores,
            'selection_history': self.selection_history
        }
        
        # Top Features über alle Methoden
        all_features = set()
        for scores in self.feature_scores.values():
            all_features.update(scores.keys())
        
        # Durchschnittliche Scores berechnen
        avg_scores = {}
        for feature in all_features:
            feature_scores = []
            for method, scores in self.feature_scores.items():
                if feature in scores:
                    # Normalisiere Scores zwischen 0 und 1
                    method_scores = np.array(list(scores.values()))
                    normalized_score = (scores[feature] - method_scores.min()) / (method_scores.max() - method_scores.min() + 1e-8)
                    feature_scores.append(normalized_score)
            
            if feature_scores:
                avg_scores[feature] = np.mean(feature_scores)
        
        # Top 20 Features
        top_features = sorted(avg_scores.items(), key=lambda x: x[1], reverse=True)[:20]
        report['top_features'] = top_features
        
        return report
    
    def save_feature_selection(self, filepath: str):
        """
        Speichere Feature Selection Ergebnisse
        """
        report = self.get_feature_importance_report()
        
        import json
        with open(filepath, 'w') as f:
            # Convert numpy types to Python types for JSON serialization
            def convert_numpy(obj):
                if isinstance(obj, np.integer):
                    return int(obj)
                elif isinstance(obj, np.floating):
                    return float(obj)
                elif isinstance(obj, np.ndarray):
                    return obj.tolist()
                return obj
            
            json.dump(report, f, indent=2, default=convert_numpy)
        
        logger.info(f"Feature Selection gespeichert: {filepath}")

# Basketball-spezifische Feature Engineering
class BasketballFeatureEngineer:
    """
    Basketball-spezifische Feature Engineering Methoden
    """
    
    def __init__(self):
        pass
    
    def create_shooting_features(self, df: pd.DataFrame) -> pd.DataFrame:
        """
        Erstelle Shooting-bezogene Features
        """
        df = df.copy()
        
        # Shooting Percentages
        if 'field_goals_made' in df.columns and 'field_goals_attempted' in df.columns:
            df['fg_percentage'] = np.where(
                df['field_goals_attempted'] > 0,
                df['field_goals_made'] / df['field_goals_attempted'],
                0
            )
        
        # Three-Point Shooting
        if 'three_pointers_made' in df.columns and 'three_pointers_attempted' in df.columns:
            df['three_point_percentage'] = np.where(
                df['three_pointers_attempted'] > 0,
                df['three_pointers_made'] / df['three_pointers_attempted'],
                0
            )
        
        # Free Throw Shooting
        if 'free_throws_made' in df.columns and 'free_throws_attempted' in df.columns:
            df['free_throw_percentage'] = np.where(
                df['free_throws_attempted'] > 0,
                df['free_throws_made'] / df['free_throws_attempted'],
                0
            )
        
        # Effective Field Goal Percentage
        if all(col in df.columns for col in ['field_goals_made', 'three_pointers_made', 'field_goals_attempted']):
            df['effective_fg_percentage'] = np.where(
                df['field_goals_attempted'] > 0,
                (df['field_goals_made'] + 0.5 * df['three_pointers_made']) / df['field_goals_attempted'],
                0
            )
        
        return df
    
    def create_efficiency_features(self, df: pd.DataFrame) -> pd.DataFrame:
        """
        Erstelle Effizienz-Features
        """
        df = df.copy()
        
        # Player Efficiency Rating (vereinfacht)
        if all(col in df.columns for col in ['points', 'rebounds', 'assists', 'steals', 'blocks']):
            df['efficiency_rating'] = (
                df['points'] + df['rebounds'] + df['assists'] + 
                df['steals'] + df['blocks']
            ) / np.maximum(df.get('minutes_played', 1), 1)
        
        # Assist to Turnover Ratio
        if 'assists' in df.columns and 'turnovers' in df.columns:
            df['assist_turnover_ratio'] = np.where(
                df['turnovers'] > 0,
                df['assists'] / df['turnovers'],
                df['assists']
            )
        
        # Rebound Rate
        if all(col in df.columns for col in ['rebounds', 'minutes_played']):
            df['rebounds_per_minute'] = df['rebounds'] / np.maximum(df['minutes_played'], 1)
        
        return df
    
    def create_defensive_features(self, df: pd.DataFrame) -> pd.DataFrame:
        """
        Erstelle Defensive Features
        """
        df = df.copy()
        
        # Defensive Actions
        defensive_cols = ['steals', 'blocks', 'defensive_rebounds']
        available_cols = [col for col in defensive_cols if col in df.columns]
        
        if available_cols:
            df['defensive_actions'] = df[available_cols].sum(axis=1)
        
        # Steal Rate
        if 'steals' in df.columns and 'minutes_played' in df.columns:
            df['steals_per_minute'] = df['steals'] / np.maximum(df['minutes_played'], 1)
        
        # Block Rate
        if 'blocks' in df.columns and 'minutes_played' in df.columns:
            df['blocks_per_minute'] = df['blocks'] / np.maximum(df['minutes_played'], 1)
        
        return df

if __name__ == "__main__":
    # Beispiel-Usage
    config = {'random_state': 42}
    selector = FeatureSelector(config)
    
    # Dummy Basketball Data
    np.random.seed(42)
    data = pd.DataFrame({
        'points': np.random.randint(0, 50, 1000),
        'rebounds': np.random.randint(0, 20, 1000),
        'assists': np.random.randint(0, 15, 1000),
        'steals': np.random.randint(0, 5, 1000),
        'blocks': np.random.randint(0, 5, 1000),
        'turnovers': np.random.randint(0, 8, 1000),
        'minutes_played': np.random.randint(10, 48, 1000),
        'field_goals_made': np.random.randint(0, 25, 1000),
        'field_goals_attempted': np.random.randint(0, 40, 1000),
        'win': np.random.choice([0, 1], 1000)
    })
    
    X = data.drop('win', axis=1)
    y = data['win']
    
    # Feature Selection
    X_selected = selector.select_features(X, y, method='auto', k_features=5)
    
    print(f"Original Features: {X.shape[1]}")
    print(f"Selected Features: {X_selected.shape[1]}")
    print(f"Selected: {X_selected.columns.tolist()}")
    
    # Report
    report = selector.get_feature_importance_report()
    print(f"Top Features: {report['top_features'][:5]}")