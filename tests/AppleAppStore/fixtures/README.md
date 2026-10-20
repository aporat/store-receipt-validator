# Fixtures

- `*.json`: recorded App Store Server API responses.
- `apple-sandbox-receipt.der`: a real StoreKit 1 sandbox app receipt signed by
  Apple, from TPInAppReceipt's test assets
  (https://github.com/tikhop/TPInAppReceipt, `Tests/Assets/receipt-sandbox-g5`,
  MIT licence, Copyright (c) Pavel Tikhonenko). It proves `AppReceiptVerifier`
  accepts Apple's real chain against the root in `resources/`.

The signing chain used to build test receipts is in `../certs/storekit`
(`generate.sh`); `forged-signer.*` there is a self-signed certificate carrying
Apple's receipt-signing and WWDR OIDs, for receipts that look like Apple's but
chain to nothing trusted.
