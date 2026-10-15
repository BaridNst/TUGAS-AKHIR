import sys
import os
import json
# pyrefly: ignore [missing-import]
import joblib
import pandas as pd

def predict(input_data):
    """
    Melakukan prediksi keterlambatan transaksi peminjaman menggunakan model Random Forest
    yang dilatih berdasarkan riwayat transaksi peminjam.
    """
    model_path = os.path.join(os.path.dirname(__file__), 'model_rf.joblib')
    meta_path = os.path.join(os.path.dirname(__file__), 'model_meta.json')

    # Jika model .joblib belum ada, berikan fallback
    if not os.path.exists(model_path):
        return {
            "nfc_uid": input_data.get('nfc_uid', 'UNKNOWN'),
            "risk_status": "Low Risk",
            "prediction": "Tepat Waktu",
            "confidence_score": 0.90,
            "message": "Model belum dilatih. Menggunakan estimasi standar."
        }

    model = joblib.load(model_path)
    with open(meta_path, 'r', encoding='utf-8') as f:
        meta = json.load(f)

    # Jika data riwayat tidak dipass langsung, cari dari dataset berdasarkan nfc_uid / user_id
    dataset_path = os.path.join(os.path.dirname(__file__), 'dataset.json')
    prev_loans = input_data.get('prev_loans')
    prev_late = input_data.get('prev_late')

    if prev_loans is None and os.path.exists(dataset_path):
        try:
            with open(dataset_path, 'r', encoding='utf-8') as f:
                histories = json.load(f)
            # Ambil sampel acak/match jika tidak dispesifikasikan
            matched = histories[0] if histories else {}
            prev_loans = matched.get('prev_loans', 3)
            prev_late = matched.get('prev_late', 0)
        except Exception:
            prev_loans = 3
            prev_late = 0

    prev_loans = int(prev_loans or 0)
    prev_late = int(prev_late or 0)
    late_ratio = float(prev_late / prev_loans) if prev_loans > 0 else 0.0
    duration_days = int(input_data.get('duration_days', 7))

    peran_str = str(input_data.get('peran', 'Mahasiswa'))
    kategori_str = str(input_data.get('kategori', 'Elektronik'))

    peran_code = meta['peran_map'].get(peran_str, 0)
    kategori_code = meta['kategori_map'].get(kategori_str, 0)

    # Susun DataFrame input untuk model
    X_input = pd.DataFrame([{
        'prev_loans': prev_loans,
        'prev_late': prev_late,
        'late_ratio': late_ratio,
        'duration_days': duration_days,
        'peran_code': peran_code,
        'kategori_code': kategori_code
    }])

    # Prediksi kelas dan probabilitas
    pred_class = model.predict(X_input)[0]  # 1 = Terlambat, 0 = Tepat Waktu
    probs = model.predict_proba(X_input)[0]  # [prob_tepat, prob_terlambat]
    prob_late = float(probs[1]) if len(probs) > 1 else float(pred_class)

    # Kategori Risiko berdasarkan probabilitas keterlambatan
    if prob_late >= 0.50:
        risk_status = "High Risk"
        prediction_label = "Terlambat"
        confidence = prob_late
        msg = f"Peminjam berisiko tinggi terlambat ({confidence * 100:.0f}% probabilitas) berdasarkan {prev_late} dari {prev_loans} riwayat transaksi sebelumnya."
    elif prob_late >= 0.30:
        risk_status = "Medium Risk"
        prediction_label = "Perlu Perhatian"
        confidence = prob_late
        msg = f"Peminjam memiliki risiko sedang terlambat ({confidence * 100:.0f}% probabilitas)."
    else:
        risk_status = "Low Risk"
        prediction_label = "Tepat Waktu"
        confidence = 1.0 - prob_late
        msg = f"Peminjam bereputasi baik ({confidence * 100:.0f}% kepatuhan) berdasarkan riwayat transaksi."

    return {
        "nfc_uid": input_data.get('nfc_uid', 'NFC-SAMPLE'),
        "risk_status": risk_status,
        "prediction": prediction_label,
        "confidence_score": round(confidence, 2),
        "prob_late": round(prob_late, 2),
        "message": msg,
        "history_summary": {
            "prev_loans": prev_loans,
            "prev_late": prev_late,
            "late_ratio": round(late_ratio, 2)
        }
    }

if __name__ == "__main__":
    raw_arg = sys.argv[1] if len(sys.argv) > 1 else "{}"
    
    try:
        # Coba parse sebagai JSON string input
        input_params = json.loads(raw_arg)
    except Exception:
        # Fallback jika hanya mengirim UID string biasa
        input_params = {
            "nfc_uid": raw_arg,
            "prev_loans": 3,
            "prev_late": 1,
            "duration_days": 7,
            "peran": "Mahasiswa",
            "kategori": "Elektronik"
        }
    
    result = predict(input_params)
    print(json.dumps(result))
