import os

path = r'f:\lock-rep-stable-projects\Necromancer\interpres\api.php'
with open(path, 'r', encoding='utf-8') as f:
    text = f.read()

target = "curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));"
replacement = """$json_encoded_data = json_encode($data, JSON_UNESCAPED_UNICODE);
        file_put_contents(__DIR__ . "/tmp_payload.txt", print_r($data, true) . "\n---\n" . $json_encoded_data . "\n========================\n", FILE_APPEND);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json_encoded_data);"""

if target in text and "tmp_payload.txt" not in text:
    text = text.replace(target, replacement)
    with open(path, 'w', encoding='utf-8', newline='\n') as f:
        f.write(text)
    print("PATCH ADDED")
else:
    print("PATCH NOT APPLIED or ALREADY EXISTS")
