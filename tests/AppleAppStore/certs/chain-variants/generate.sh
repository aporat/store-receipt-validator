#!/usr/bin/env sh
# Regenerates the certificate chains used by TokenVerifierTest.
#
# A second, independent chain shaped like Apple's (root -> WWDR-style intermediate
# -> App Store signing leaf), plus variants that each break one rule:
#
#   root.pem                     Variant Root CA (self-signed)
#   intermediate.pem             issued by root.pem; CA, WWDR marker 1.2.840.113635.100.6.2.1
#   leaf.pem                     issued by intermediate.pem; marker 1.2.840.113635.100.6.11.1
#   leaf-no-marker.pem           leaf.pem without its marker
#   intermediate-no-marker.pem   intermediate.pem (same key and subject) without its marker
#   intermediate-not-ca.pem      intermediate.pem (same key and subject) with CA:FALSE
#   other-root.pem               an unrelated self-signed root
#   impostor-root.pem            same subject as root.pem, different key
#   intermediate-by-impostor.pem intermediate.pem's key and subject, issued by impostor-root.pem
#
# Every leaf uses leaf.p8, the key the tests sign JWS payloads with. CA keys are discarded.
set -eu
cd "$(dirname "$0")"
tmp=$(mktemp -d)
trap 'rm -rf "$tmp"' EXIT

key() { openssl ecparam -name prime256v1 -genkey -noout -out "$1"; }
subj() { printf '/CN=%s/O=store-receipt-validator chain variants' "$1"; }

# sign <csr> <ca.pem> <ca.key> <out.pem> <extensions...>
sign() {
    csr=$1 ca=$2 cakey=$3 out=$4
    shift 4
    printf '%s\n' "$@" > "$tmp/ext"
    openssl x509 -req -in "$csr" -CA "$ca" -CAkey "$cakey" -set_serial "0x$(openssl rand -hex 8)" \
        -days 18000 -sha256 -extfile "$tmp/ext" -out "$out" 2>/dev/null
}

root() { # root <key> <cn> <out>
    openssl req -x509 -new -key "$1" -sha256 -days 18250 -subj "$(subj "$2")" \
        -addext 'basicConstraints=critical,CA:TRUE' -addext 'keyUsage=critical,keyCertSign,cRLSign' -out "$3"
}

CA_EXT='basicConstraints=critical,CA:TRUE,pathlen:0'
NOT_CA_EXT='basicConstraints=critical,CA:FALSE'
CA_USAGE='keyUsage=critical,keyCertSign,cRLSign'
LEAF_USAGE='keyUsage=critical,digitalSignature'
WWDR='1.2.840.113635.100.6.2.1=ASN1:NULL'
APP_STORE='1.2.840.113635.100.6.11.1=ASN1:NULL'

key "$tmp/root.key";     root "$tmp/root.key" 'Variant Root CA' root.pem
key "$tmp/other.key";    root "$tmp/other.key" 'Other Root CA' other-root.pem
key "$tmp/impostor.key"; root "$tmp/impostor.key" 'Variant Root CA' impostor-root.pem

key "$tmp/int.key"
openssl req -new -key "$tmp/int.key" -subj "$(subj 'Variant WWDR Intermediate')" -out "$tmp/int.csr"
sign "$tmp/int.csr" root.pem "$tmp/root.key" intermediate.pem "$CA_EXT" "$CA_USAGE" "$WWDR"
sign "$tmp/int.csr" root.pem "$tmp/root.key" intermediate-no-marker.pem "$CA_EXT" "$CA_USAGE"
sign "$tmp/int.csr" root.pem "$tmp/root.key" intermediate-not-ca.pem "$NOT_CA_EXT" "$CA_USAGE" "$WWDR"
sign "$tmp/int.csr" impostor-root.pem "$tmp/impostor.key" intermediate-by-impostor.pem "$CA_EXT" "$CA_USAGE" "$WWDR"

key "$tmp/leaf.key"
openssl req -new -key "$tmp/leaf.key" -subj "$(subj 'Variant App Store Signing')" -out "$tmp/leaf.csr"
sign "$tmp/leaf.csr" intermediate.pem "$tmp/int.key" leaf.pem 'basicConstraints=critical,CA:FALSE' "$LEAF_USAGE" "$APP_STORE"
sign "$tmp/leaf.csr" intermediate.pem "$tmp/int.key" leaf-no-marker.pem 'basicConstraints=critical,CA:FALSE' "$LEAF_USAGE"
openssl pkcs8 -topk8 -nocrypt -in "$tmp/leaf.key" -out leaf.p8
