import json
import os
# pyrefly: ignore [missing-import]
import joblib
import pandas as pd
import numpy as np
from sklearn.ensemble import RandomForestClassifier
from sklearn.model_selection import train_test_split
from sklearn.metrics import accuracy_score, classification_report

def train_model():
    dataset_path = os.path.join(os.path.dirname(__file__), 'dataset.json')
    if not os.path.exists(dataset_path):
        print(f"Error: Dataset {dataset_path} tidak ditemukan.")
        return

    with open(dataset_path, 'r', encoding='utf-8') as f:
        data = json.load(f)

    df = pd.DataFrame(data)
    print(f"Loaded {len(df)} historical transaction samples.")

    # Encoding kategori & peran
    kategori_map = {'Elektronik': 0, 'Jaringan': 1, 'Komputer': 2, 'Perkakas': 3, 'Aksesori': 4}
    peran_map = {'Mahasiswa': 0, 'Dosen': 1}

    df['kategori_code'] = df['kategori'].map(lambda x: kategori_map.get(x, 0))
    df['peran_code'] = df['peran'].map(lambda x: peran_map.get(x, 0))

    feature_cols = ['prev_loans', 'prev_late', 'late_ratio', 'duration_days', 'peran_code', 'kategori_code']
    X = df[feature_cols]
    y = df['is_late']

    # Train / Test split
    X_train, X_test, y_train, y_test = train_test_split(X, y, test_size=0.2, random_state=42, stratify=y)

    # Inisialisasi & Training Random Forest Classifier
    rf = RandomForestClassifier(
        n_estimators=100,
        max_depth=6,
        criterion='gini',
        random_state=42
    )
    rf.fit(X_train, y_train)

    # Evaluasi Model
    y_pred = rf.predict(X_test)
    acc = accuracy_score(y_test, y_pred)
    print(f"\n--- Random Forest Model Trained ---")
    print(f"Akurasi Model: {acc * 100:.2f}%")
    print("\nFeature Importances:")
    for col, imp in zip(feature_cols, rf.feature_importances_):
        print(f" - {col}: {imp:.4f}")

    # Simpan Model & Metadata
    model_path = os.path.join(os.path.dirname(__file__), 'model_rf.joblib')
    joblib.dump(rf, model_path)

    meta_path = os.path.join(os.path.dirname(__file__), 'model_meta.json')
    meta_data = {
        'feature_cols': feature_cols,
        'kategori_map': kategori_map,
        'peran_map': peran_map,
        'accuracy': float(acc),
        'total_samples': len(df)
    }
    with open(meta_path, 'w', encoding='utf-8') as f:
        json.dump(meta_data, f, indent=4)

    print(f"\nModel berhasil disimpan ke: {model_path}")
    print(f"Metadata disimpan ke: {meta_path}")

if __name__ == '__main__':
    train_model()
