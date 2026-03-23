<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $appName }} Console</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <style>
        :root { color-scheme: light; }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            background: #f5f7fb;
            color: #0f172a;
        }
        header {
            padding: 24px;
            background: linear-gradient(120deg, #4f46e5, #10b981);
            color: #fff;
        }
        main { padding: 24px; max-width: 1100px; margin: 0 auto; }
        h1 { margin: 0 0 6px 0; font-size: 26px; }
        .card {
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 18px 40px rgba(15, 23, 42, 0.08);
            padding: 20px;
            margin-bottom: 16px;
        }
        label { display: block; font-weight: 600; margin-bottom: 6px; }
        input, select, button, textarea {
            font: inherit;
            padding: 10px 12px;
            border-radius: 10px;
            border: 1px solid #cbd5e1;
            width: 100%;
        }
        button {
            background: #2563eb;
            color: #fff;
            border: none;
            cursor: pointer;
            transition: all 0.15s ease;
        }
        button:hover { background: #1d4ed8; }
        .row { display: grid; gap: 12px; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); }
        pre {
            background: #0f172a;
            color: #e2e8f0;
            padding: 12px;
            border-radius: 10px;
            max-height: 260px;
            overflow: auto;
        }
        .pill { display: inline-flex; align-items: center; gap: 6px; background: #e0f2fe; color: #0369a1; padding: 6px 10px; border-radius: 999px; font-size: 12px; }
    </style>
</head>
<body>
<header>
    <h1>{{ $appName }} Console</h1>
    <div class="pill">Env: {{ $appEnv }}</div>
</header>
<main>
    <div class="card">
        <h2>API Base</h2>
        <p style="margin:0 0 8px 0">{{ $apiBase }}</p>
        <small>Gunakan domain ini untuk semua endpoint mobile dan panel.</small>
    </div>

    <div class="card">
        <h2>Login & Token</h2>
        <div class="row">
            <div>
                <label>Email</label>
                <input id="email" value="admin@kinaya.test">
            </div>
            <div>
                <label>Password</label>
                <input id="password" type="password" value="password">
            </div>
            <div>
                <label>Branch ID</label>
                <input id="branchId" type="number" value="1">
            </div>
        </div>
        <div style="margin-top:12px; display:flex; gap:8px; flex-wrap:wrap;">
            <button onclick="doLogin()">Login & Save Token</button>
            <button style="background:#0ea5e9" onclick="getBranches()">List Branches</button>
            <button style="background:#10b981" onclick="getProducts()">List Products</button>
        </div>
        <p id="tokenStatus" style="margin-top:10px;font-weight:600;color:#065f46;"></p>
    </div>

    <div class="card">
        <h2>Output</h2>
        <pre id="output">// response akan tampil di sini</pre>
    </div>
</main>

<script>
const apiBase = "{{ $apiBase }}";

function setOutput(data) {
    const out = document.getElementById('output');
    out.textContent = typeof data === 'string' ? data : JSON.stringify(data, null, 2);
}

function saveToken(token) {
    localStorage.setItem('kinaya_token', token);
    document.getElementById('tokenStatus').textContent = 'Token tersimpan';
}

async function doLogin() {
    const email = document.getElementById('email').value;
    const password = document.getElementById('password').value;
    try {
        const res = await fetch(apiBase + '/login', {
            method: 'POST',
            headers: {'Content-Type':'application/json', 'Accept':'application/json'},
            body: JSON.stringify({email, password})
        });
        const data = await res.json();
        if (!res.ok) throw data;
        saveToken(data.token);
        setOutput(data);
    } catch (e) {
        setOutput(e);
    }
}

function authHeaders() {
    const token = localStorage.getItem('kinaya_token');
    const branchId = document.getElementById('branchId').value || 1;
    return {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'Authorization': token ? `Bearer ${token}` : '',
        'X-Branch-ID': branchId
    };
}

async function getBranches() {
    try {
        const res = await fetch(apiBase + '/branches', { headers: authHeaders() });
        const data = await res.json();
        setOutput(data);
    } catch (e) { setOutput(e); }
}

async function getProducts() {
    try {
        const res = await fetch(apiBase + '/products', { headers: authHeaders() });
        const data = await res.json();
        setOutput(data);
    } catch (e) { setOutput(e); }
}
</script>
</body>
</html>
