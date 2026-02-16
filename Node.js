// productController.js - Multiple Modern Vulnerabilities
const express = require('express');
const { MongoClient } = require('mongodb');
const multer = require('multer');
const crypto = require('crypto');
const { exec } = require('child_process');
const path = require('path');
const fs = require('fs');

const app = express();
app.use(express.json({ limit: '50mb' }));
app.use(express.urlencoded({ extended: true }));

// Weak MongoDB connection (no TLS)
const client = new MongoClient('mongodb://localhost:27017/ecommerce');
client.connect();

// A01:2021 - Broken Object Level Authorization (BOLA)
app.delete('/api/products/:id', async (req, res) => {
    const { id } = req.params;
    // VULN: No ownership check! (OWASP API Security Top 10 #1)
    await client.db('ecommerce').collection('products').deleteOne({ _id: id });
    res.json({ success: true });
});

// A03:2021 - NoSQL Injection (CVE-2023-45147 similar)
app.get('/api/products/search', async (req, res) => {
    const { q } = req.query;
    // FIXED: Use regex with proper escaping instead of $where
    const escapedQuery = q.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    const products = await client.db('ecommerce').collection('products')
        .find({ name: { $regex: escapedQuery, $options: 'i' } })
        .toArray();
    res.json(products);
});

// A05:2021 - SSRF (CVE-2024-23690 like)
app.post('/api/preview', async (req, res) => {
    const { url } = req.body;
    // VULN: Unvalidated URL → SSRF
    const { exec } = require('child_process');
    exec(`curl -s "${url}"`, (err, stdout) => {
        res.send(stdout);
    });
});

// A07:2021 - Session Fixation + Weak JWT (CVE-2024-23307)
app.post('/api/login', (req, res) => {
    const { username, password } = req.body;
    // VULN: Predictable JWT (HS256 with static secret)
    const token = jwt.sign({ username }, 'weak_secret_key_123');
    res.json({ token });
});

// A02:2021 - Prototype Pollution (CVE-2024-23307)
app.put('/api/profile', (req, res) => {
    const updates = req.body;
    // VULN: Direct merge → prototype pollution!
    Object.assign(req.user, updates);
    res.json(req.user);
});

// A06:2021 - Vulnerable Components (lodash CVE-2021-23337)
const _ = require('lodash'); // Vulnerable version <4.17.21
app.get('/api/merge', (req, res) => {
    const data = _.merge({}, req.query);
    res.json(data);
});

// A08:2021 - File Upload (Path Traversal CVE-2023-30588)
const upload = multer({ dest: 'uploads/' });
app.post('/api/upload', upload.single('file'), (req, res) => {
    // VULN: No filename sanitization
    const newPath = path.join('uploads', req.body.filename);
    fs.renameSync(req.file.path, newPath);
    res.json({ path: newPath });
});

// A10:2021 - XXE (CVE-2024-21592 libxmljs)
app.post('/api/xml-import', express.raw({ type: 'application/xml' }), (req, res) => {
    const xml2js = require('xml2js');
    // VULN: External entity processing
    xml2js.parseString(req.body, (err, result) => {
        res.json(result);
    });
});

app.listen(3000, () => console.log('Vulnerable API on port 3000'));