#!/usr/bin/env sh
# Regenerates the test certificate chain used by VerifySignedPayloadTest.
#
# The chain mirrors Apple's StoreKit signing layout:
#   root.pem -> intermediate.pem (WWDR marker 1.2.840.113635.100.6.2.1)
#            -> leaf.pem (App Store marker 1.2.840.113635.100.6.11.1)
# plus negative variants without the marker extensions. Every leaf shares
# leaf.p8, the key tests sign JWS payloads with. CA keys are discarded.
set -eu
cd "$(dirname "$0")"
tmp=$(mktemp -d)
trap 'rm -rf "$tmp"' EXIT

key() { openssl ecparam -name prime256v1 -genkey -noout -out "$1"; }

# sign <csr> <ca.pem> <ca.key> <out.pem> <extensions...>
sign() {
    csr=$1 ca=$2 cakey=$3 out=$4
    shift 4
    printf '%s\n' "$@" > "$tmp/ext"
    openssl x509 -req -in "$csr" -CA "$ca" -CAkey "$cakey" -set_serial "0x$(openssl rand -hex 8)" \
        -days 18000 -sha256 -extfile "$tmp/ext" -out "$out"
}

subj() { printf '/CN=%s/O=store-receipt-validator tests' "$1"; }

key "$tmp/root.key"
openssl req -x509 -new -key "$tmp/root.key" -sha256 -days 18250 -subj "$(subj 'Test Root CA')" \
    -addext 'basicConstraints=critical,CA:TRUE' -addext 'keyUsage=critical,keyCertSign,cRLSign' -out root.pem

CA_EXT='basicConstraints=critical,CA:TRUE,pathlen:0'
CA_USAGE='keyUsage=critical,keyCertSign,cRLSign'
LEAF_EXT='basicConstraints=critical,CA:FALSE'
LEAF_USAGE='keyUsage=critical,digitalSignature'

key "$tmp/int.key"
openssl req -new -key "$tmp/int.key" -subj "$(subj 'Test Intermediate CA')" -out "$tmp/int.csr"
sign "$tmp/int.csr" root.pem "$tmp/root.key" intermediate.pem "$CA_EXT" "$CA_USAGE" '1.2.840.113635.100.6.2.1=ASN1:NULL'
sign "$tmp/int.csr" root.pem "$tmp/root.key" intermediate-no-marker.pem "$CA_EXT" "$CA_USAGE"

key "$tmp/leaf.key"
openssl req -new -key "$tmp/leaf.key" -subj "$(subj 'Test App Store Signing')" -out "$tmp/leaf.csr"
sign "$tmp/leaf.csr" intermediate.pem "$tmp/int.key" leaf.pem "$LEAF_EXT" "$LEAF_USAGE" '1.2.840.113635.100.6.11.1=ASN1:NULL'
sign "$tmp/leaf.csr" intermediate.pem "$tmp/int.key" leaf-no-marker.pem "$LEAF_EXT" "$LEAF_USAGE"
openssl pkcs8 -topk8 -nocrypt -in "$tmp/leaf.key" -out leaf.p8
