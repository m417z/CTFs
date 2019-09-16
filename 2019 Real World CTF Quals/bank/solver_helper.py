import os
import sys
import socketserver
import base64 as b64
import hashlib
from Crypto.Util import number
from Crypto import Random
from Crypto.PublicKey.pubkey import *
import datetime
import calendar
from itertools import chain, product

from schnorr import *

MSGLENGTH = 40000
HASHLENGTH = 16
FLAG = '???'#open("flag","r").read()
PORT_NUM = 20014

def digitalize(m):
    return int(m.encode('hex'), 16)

def find_test(proof):
    prefix = proof
    alphabet = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"
    for suffix in map(''.join, chain.from_iterable(product(alphabet, repeat=i) for i in range(5,6))):
        # suffix = b64.b64encode(counter.to_bytes(4, byteorder='big')).decode()
        test = ''.join([prefix, suffix])
        ha = hashlib.sha1()
        ha.update(test.encode())
        if (ha.digest()[-1] == 0 and ha.digest()[-2] == 0):
            return test
        # if (counter % 100000 == 0):
        #     print("{} {} {}\n".format(len(b64.b64encode(test.encode())), counter, test))
        # counter = counter + 1

def handle():
    Random.atfork()
    proof = b64.b64encode(os.urandom(12)).decode()

    print(
        "Please provide your proof of work, a sha1 sum ending in 16 bit's set to 0, it must be of length %d bytes, starting with %s\n" % (
        len(proof) + 5, proof))

    #test = sys.stdin.readline().strip()
    test = find_test(proof)

    ha = hashlib.sha1()
    ha.update(test.encode())

    if (test[0:16] != proof or ha.digest()[-1] != 0 or ha.digest()[-2] != 0): # or ha.digest()[-3] != 0 or ha.digest()[-4] != 0):
        print("Check failed")
        return

    print("Generating keys...\n")
    sk, pk = generate_keys()
    balance = 0
    while True:
            print("Please tell us your public key:")
            msg = sys.stdin.readline().strip()
            if len(msg) < 6 or len(msg) > MSGLENGTH:
                print("what are you doing?")
                return
            userPk = (int(msg.split(',')[0]), int(msg.split(',')[1]))
            print('''User logged in.

            [Beep]

Please select your options:

1. Deposit a coin into your account, you can sign a message 'DEPOSIT' and send us the signature.
2. Withdraw a coin from your account, you need to provide us a message 'WITHDRAW' signed by both of you and our RESPECTED BANK MANAGER.
3. Find one of our customer support representative to assist you.


Our working hour is 9:00 am to 5:00 pm every %s!
Thank you for being our loyal customer and your satisfaction is our first priority!
''' % calendar.day_name[(datetime.datetime.today() + datetime.timedelta(days=1)).weekday()])
            msg = sys.stdin.readline().strip()
            if msg[0] == '1':
                print("Please send us your signature")
                msg = b64.b64decode(sys.stdin.readline().strip()).decode()
                if schnorr_verify('DEPOSIT', userPk, msg):
                    balance += 1
                print("Coin deposited.\n")
            elif msg[0] == '2':
                print("Please send us your signature")
                msg = b64.b64decode(sys.stdin.readline().strip()).decode()
                if schnorr_verify('WITHDRAW', point_add(userPk, pk), msg) and balance > 0:
                    print(("Here is your coin: %s\n" % FLAG))
            elif msg[0] == '3':
                print("The custom service is offline now.\n\nBut here is our public key just in case a random guy claims himself as one of us: %s\n" % repr(pk))

if __name__ == "__main__":
    if len(sys.argv) >= 2:
        if sys.argv[1] == '1':
            print(find_test(sys.argv[2]))
        elif sys.argv[1] == '2':
            print(b64.b64encode(schnorr_sign(sys.argv[2].encode(), 0x0000000000000000000000000000000000000000000000000000000000000001)).decode())
        elif sys.argv[1] == '3':
            new_key = point_add((int(sys.argv[2]), int(sys.argv[3])), (int(sys.argv[4]), -int(sys.argv[5])))
            print(','.join([str(new_key[0]), str(new_key[1])]))
        exit()

    print("Proof prefix:")
    proof = sys.stdin.readline().strip()
    test = find_test(proof)
    print("Proof: {}\n".format(test))

    handle()
