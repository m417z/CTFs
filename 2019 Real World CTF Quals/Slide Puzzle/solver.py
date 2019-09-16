import sys
import subprocess
import base64
import requests
import json
import re
import fileinput
import numpy as np

def chunks(l, n):
    """Yield successive n-sized chunks from l."""
    for i in range(0, len(l), n):
        yield l[i:i + n]

def get_name():
    print('Insert r:\n')
    r = [[int(x) for x in sys.stdin.readline().strip().split(',')] for i in range(0, 35)]

    print('Insert l:\n')
    l = sys.stdin.readline().strip()
    l = [int(x) for x in l.split(',')]

    print('===============')
    print(r)
    print(l)

    a = np.array(r)
    b = np.array(l)
    x = np.linalg.solve(a, b)
    name = ''.join(chr(int(round(i))) for i in x)
    print(name)
    return name

def row_col_to_index(row_col):
    row, col = row_col
    return int(row) * 4 + int(col)

def main(argv):
    prox = {} #{"http": "http://127.0.0.1:8888"}

    sess = requests.session()
    contents = sess.get("http://web.realworldctf.com:44444/api/new_game?width=4&height=4", proxies=prox).text
    with open('_api_new_game.txt', 'w') as the_file:
        the_file.write(contents)

    data_base64 = json.loads(contents)['data']
    data = base64.b64decode(data_base64)

    #data = [0,1,2,3,4,5,6,7,8,9,10,11,15,12,13,14]
    data = [x + 1 if x < 15 else 0 for x in data]

    with open('_in.txt', 'w') as the_file:
        the_file.write("4\n{} {} {} {}\n{} {} {} {}\n{} {} {} {}\n{} {} {} {}\n".format(*data))

    # https://github.com/GuptaAnna/15418Project
    result = subprocess.check_output(['./run', '-t', '4', '-f', '_in.txt']).decode('utf-8')

    with open('_out.txt', 'w') as the_file:
        the_file.write(result)

    row_col = re.compile(r'emptyRow=(\d+) emptyCol=(\d+)').findall(result)
    row_col = [row_col_to_index(x) for x in row_col[2:]]

    boards = re.compile(r'(\d+) (\d+) (\d+) (\d+) \n(\d+) (\d+) (\d+) (\d+) \n(\d+) (\d+) (\d+) (\d+) \n(\d+) (\d+) (\d+) (\d+) \n').findall(result)
    boards = [[int(x) for x in board] for board in boards[1:-1]]

    path = [board[i] for i, board in zip(row_col, boards)]

    path = [x - 1 if x > 0 else 15 for x in path]

    response = sess.post("http://web.realworldctf.com:44444/api/verify", data=json.dumps({'data': base64.b64encode(bytearray(path)).decode('utf-8')}), proxies=prox).text
    print(response)
    with open('_api_verify.txt', 'w') as the_file:
        the_file.write(contents)

    #name = 'm417z'
    name = get_name()

    #response = sess.post("http://web.realworldctf.com:44444/api/submit", data=json.dumps({'name': name}), proxies=prox).text
    #print(response)

if __name__ == "__main__":
    main(sys.argv[1:])
    #get_name()
