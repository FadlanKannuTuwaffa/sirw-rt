import json

cases = []
case_id = 1

bills_periods = [
    ("bulan ini", {"period": "current_month"}),
    ("bulan lalu", {"period": "previous_month"}),
    ("bulan depan", {"period": "next_month"}),
]

bill_focus_terms = [
    ("", {}),
    ("kebersihan", {"include": "kebersihan"}),
    ("keamanan", {"include": "keamanan"}),
    ("kas", {"include": "kas"}),
    ("sampah", {"include": "sampah"}),
]

bill_templates_general = [
    "Apa saja tagihan {period} yang belum lunas?",
    "Daftar tunggakan {period} dong",
    "Tagihan {period} apa yang harus kubayar?",
]

bill_templates_focus = [
    "Ada tagihan {focus} {period} yang belum dibayar?",
    "Cek iuran {focus} {period} ya",
]

for tmpl in bill_templates_general:
    for label, slot in bills_periods:
        cases.append({
            "id": f"BILLS_{case_id:03}",
            "question": tmpl.format(period=label),
            "expected_intent": "bills",
            "expected_slots": slot,
            "requires_tool": True,
            "requires_auth": True,
            "category": "bills"
        })
        case_id += 1

for tmpl in bill_templates_focus:
    for label, slot in bills_periods:
        for focus_label, focus_slot in bill_focus_terms[1:]:
            merged_slots = {**slot, **focus_slot}
            cases.append({
                "id": f"BILLS_{case_id:03}",
                "question": tmpl.format(focus=focus_label, period=label),
                "expected_intent": "bills",
                "expected_slots": merged_slots,
                "requires_tool": True,
                "requires_auth": True,
                "category": "bills"
            })
            case_id += 1

payments_templates_general = [
    "Riwayat pembayaran {period} apa aja?",
    "Tunjukkan pembayaran yang masuk {period}",
    "Pembayaran {period} mana yang lunas?",
]

for tmpl in payments_templates_general:
    for label, slot in bills_periods:
        cases.append({
            "id": f"PAY_{case_id:03}",
            "question": tmpl.format(period=label),
            "expected_intent": "payments",
            "expected_slots": {"period": slot["period"]},
            "requires_tool": True,
            "requires_auth": True,
            "category": "payments"
        })
        case_id += 1

agenda_ranges = [
    ("hari ini", "today"),
    ("besok", "tomorrow"),
    ("minggu ini", "week"),
    ("minggu depan", "next_week"),
]

agenda_templates = [
    "Agenda {label} apa saja?",
    "Ada kegiatan {label}?",
    "Tolong kirim jadwal {label}",
]

for tmpl in agenda_templates:
    for label, range_key in agenda_ranges:
        cases.append({
            "id": f"AGENDA_{case_id:03}",
            "question": tmpl.format(label=label),
            "expected_intent": "agenda",
            "expected_slots": {"range": range_key},
            "requires_tool": True,
            "category": "agenda"
        })
        case_id += 1

finance_templates = [
    "Rekap keuangan {period} dong",
    "Hitung pemasukan dan pengeluaran {period}",
    "Status kas {period} gimana?",
]

for tmpl in finance_templates:
    for label, slot in bills_periods:
        cases.append({
            "id": f"FIN_{case_id:03}",
            "question": tmpl.format(period=label),
            "expected_intent": "finance",
            "expected_slots": {"period": slot["period"]},
            "requires_tool": True,
            "category": "finance"
        })
        case_id += 1

knowledge_topics = [
    "prosedur surat domisili",
    "cara pinjam balai warga",
    "aturan iuran keamanan",
    "jadwal pengambilan sampah",
    "proses surat pindah",
    "cara ajukan SKCK",
    "langkah minta surat pengantar",
    "panduan ronda malam",
    "alur pengajuan bantuan warga",
    "aturan penggunaan lapangan",
]

for topic in knowledge_topics:
    cases.append({
        "id": f"KB_{case_id:03}",
        "question": f"Bagaimana {topic}?",
        "expected_intent": "knowledge_base",
        "requires_kb": True,
        "category": "kb"
    })
    case_id += 1

residents_prompts = [
    ("Berapa total warga terdaftar sekarang?", "residents"),
    ("Tolong cari warga bernama Budi", "residents"),
    ("Siapa saja warga baru bulan ini?", "residents_new"),
    ("Butuh kontak pengurus keamanan", "residents"),
    ("Cari warga bernama Dewi blok C", "residents"),
    ("Ada warga bernama Andi yang belum update?", "residents"),
]

for prompt, intent in residents_prompts:
    cases.append({
        "id": f"RES_{case_id:03}",
        "question": prompt,
        "expected_intent": intent,
        "category": "residents"
    })
    case_id += 1

multi_intent_cases = [
    {
        "id": "MULTI_001",
        "question": "Aku butuh agenda minggu ini dan tagihan kebersihan yang belum lunas",
        "expected_intent": "multi_intent",
        "multi_intent_targets": ["agenda", "bills"],
    },
    {
        "id": "MULTI_002",
        "question": "Tolong kirim ringkasan pembayaran bulan ini sekaligus daftar warga baru",
        "expected_intent": "multi_intent",
        "multi_intent_targets": ["payments", "residents_new"],
    },
    {
        "id": "MULTI_003",
        "question": "Agenda minggu depan dan status kas sekarang apa?",
        "expected_intent": "multi_intent",
        "multi_intent_targets": ["agenda", "finance"],
    },
    {
        "id": "MULTI_004",
        "question": "Cek tagihan kebersihan dan pembayaran yang sudah lunas bulan ini",
        "expected_intent": "multi_intent",
        "multi_intent_targets": ["bills", "payments"],
    },
]

cases.extend(multi_intent_cases)

retry_cases = [
    {
        "id": "RETRY_001",
        "question": "Cek tagihan listrik dong",
        "follow_up": "Eh maksudku tagihan kebersihan ya",
        "expected_intent": "bills",
        "expected_slots": {"include": "listrik"},
        "expected_intent_after_follow_up": "bills",
        "expected_slots_after_follow_up": {"include": "kebersihan"},
        "category": "retry",
    },
    {
        "id": "RETRY_002",
        "question": "Cari warga bernama Dewi",
        "follow_up": "Maaf, ganti jadi Dwi ya",
        "expected_intent": "residents",
        "expected_intent_after_follow_up": "residents",
        "category": "retry",
    },
]

cases.extend(retry_cases)

negation_cases = [
    {
        "id": "NEG_001",
        "question": "Aku tidak mau info tagihan keamanan, cukup yang kebersihan saja",
        "expected_intent": "bills",
        "expected_slots": {"include": "kebersihan", "exclude": "keamanan"},
        "category": "negation",
    },
    {
        "id": "NEG_002",
        "question": "Jangan tampilkan agenda minggu ini, cek minggu depan aja",
        "expected_intent": "agenda",
        "expected_slots": {"range": "next_week"},
        "category": "negation",
    },
]

cases.extend(negation_cases)

slang_variants = ['dong', 'ya', 'pls', 'nih', 'tolong dong', 'bang']
extra_cases = []
for idx, base in enumerate(cases):
    if len(cases) + len(extra_cases) >= 170:
        break
    extra = dict(base)
    extra['id'] = f"{base['id']}_VAR"
    extra['question'] = f"{base['question']} {slang_variants[idx % len(slang_variants)]}"
    extra_cases.append(extra)

cases.extend(extra_cases)

with open('tests/data/assistant_eval_full.json', 'w', encoding='utf-8') as f:
    json.dump(cases, f, ensure_ascii=False, indent=2)

print(f"Generated {len(cases)} cases")
