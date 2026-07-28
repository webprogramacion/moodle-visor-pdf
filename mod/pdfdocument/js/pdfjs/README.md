# PDF.js vendor directory

The protected viewer renders PDF pages to `<canvas>` using Mozilla's
[PDF.js](https://github.com/mozilla/pdf.js). The required files are **bundled**
in this directory:

```
pdf.min.js          <- main library (PDF.js 4.x legacy build, ES module)
pdf.worker.min.js   <- worker (ES module)
```

These come from `pdfjs-dist@4.10.38`, files `legacy/build/pdf.min.mjs` and
`legacy/build/pdf.worker.min.mjs`, renamed to `.js` so every web server sends a
`text/javascript` MIME type (some servers do not map `.mjs`). They are still ES
modules; `amd/src/viewer.js` loads them with a native dynamic `import()`.

## Upgrading

1. Fetch a newer 4.x build:
   ```bash
   npm pack pdfjs-dist@4
   tar -xzf pdfjs-dist-4.*.tgz
   cp package/legacy/build/pdf.min.mjs        pdf.min.js
   cp package/legacy/build/pdf.worker.min.mjs pdf.worker.min.js
   ```
2. Update the `<version>` in `mod/pdfdocument/thirdpartylibs.xml`.

No code change is needed as long as PDF.js keeps the ES-module named exports
`getDocument` and `GlobalWorkerOptions`.

## License

PDF.js is licensed under Apache-2.0. Keep this notice with the vendored files.
