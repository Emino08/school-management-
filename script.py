from pathlib import Path
text = Path('database updated files/updated.sql').read_text(encoding='utf-8')
idx = text.find('CREATE TABLE `subject_results`')
print(idx)
print(text[idx:idx+1200])
