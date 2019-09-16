const Buffer = require('safe-buffer').Buffer;
const BigInteger = require('bigi');
const schnorr = require('bip-schnorr');
const convert = schnorr.convert;

// Quickest way I found to convert a public key to point is by using the bip-schnorr npm module
const publicKey = Buffer.from('0279BE667EF9DCBBAC55A06295CE870B07029BFCDB2DCE28D959F2815B16F81798', 'hex');
console.log(convert.pubKeyToPoint(publicKey).x.toString());
console.log(convert.pubKeyToPoint(publicKey).y.toString());
