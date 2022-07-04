from pathlib import Path


data = Path('dump.zip').read_bytes()

split = data.split(b'PK\x05\x06')

result = ''
for item in split[1:]:
    offset_binary = item[-6:-2]
    offset = offset_binary[0] + offset_binary[1] * 256 + offset_binary[2] * 256 * 256 + offset_binary[3] * 256 * 256 * 256
    result += chr(data[offset - 1])

print(result)
