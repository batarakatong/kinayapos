<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{{ $appName }} — Admin Panel</title>
<script src="https://cdn.tailwindcss.com"></script>
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
  [x-cloak]{ display:none!important; }
  body{ font-family:'Inter',sans-serif; }
  .sl{ display:flex;align-items:center;gap:12px;padding:10px 16px;border-radius:12px;font-size:.875rem;font-weight:500;transition:all .15s;width:100%;text-align:left;cursor:pointer; }
  .sl.on{ background:#4f46e5;color:#fff; }
  .sl:not(.on){ color:#cbd5e1; }
  .sl:not(.on):hover{ background:#334155;color:#fff; }
  .inp{ width:100%;border:1px solid #e2e8f0;border-radius:12px;padding:8px 14px;font-size:.875rem;outline:none; }
  .inp:focus{ box-shadow:0 0 0 2px #6366f1; }
  .btn-pri{ background:#4f46e5;color:#fff;border-radius:12px;padding:8px 18px;font-size:.875rem;font-weight:600;cursor:pointer; }
  .btn-pri:hover{ background:#4338ca; }
  .btn-sec{ background:transparent;border:1px solid #e2e8f0;border-radius:12px;padding:8px 18px;font-size:.875rem;cursor:pointer; }
  .btn-sec:hover{ background:#f8fafc; }
  .badge{ display:inline-block;font-size:.7rem;font-weight:600;padding:2px 8px;border-radius:999px; }
  .card{ background:#fff;border-radius:1rem;box-shadow:0 1px 3px rgba(0,0,0,.07); }
  table{ width:100%;font-size:.875rem; }
  thead tr{ background:#f8fafc;font-size:.7rem;text-transform:uppercase;letter-spacing:.05em;color:#94a3b8; }
  th,td{ padding:12px 16px;text-align:left; }
  tbody tr{ border-top:1px solid #f1f5f9; }
  tbody tr:hover{ background:#f8fafc; }
  .modal-bg{ position:fixed;inset:0;background:rgba(0,0,0,.4);display:flex;align-items:center;justify-content:center;z-index:50;padding:16px; }
  .modal-box{ background:#fff;border-radius:1.25rem;box-shadow:0 20px 60px rgba(0,0,0,.2);width:100%;max-width:520px;padding:24px;max-height:90vh;overflow-y:auto; }
  label.lbl{ display:block;font-size:.75rem;font-weight:500;color:#475569;margin-bottom:4px; }
  .grid2{ display:grid;grid-template-columns:1fr 1fr;gap:12px; }
  .grid3{ display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px; }
</style>
</head>
<body class="bg-slate-100" x-data="adminApp('{{ $apiBase }}')" x-init="init()">

{{-- ═══════════════ LOGIN ═══════════════ --}}
<div x-show="!token" x-cloak class="min-h-screen flex items-center justify-center bg-gradient-to-br from-indigo-700 to-emerald-500">
  <div class="bg-white rounded-2xl shadow-2xl p-8 w-full max-w-sm">
    <div class="text-center mb-6">
      <div class="w-16 h-16 bg-indigo-600 rounded-2xl flex items-center justify-center mx-auto mb-3">
        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
        </svg>
      </div>
      <h1 class="text-xl font-bold text-slate-800">{{ $appName }}</h1>
      <p class="text-slate-400 text-xs mt-1">Admin Panel · Super Admin Only</p>
    </div>
    <div class="space-y-3">
      <div><label class="lbl">Email</label><input x-model="lf.email" type="email" class="inp" placeholder="admin@kinaya.test"></div>
      <div><label class="lbl">Password</label><input x-model="lf.password" type="password" class="inp" placeholder="••••••••" @keydown.enter="doLogin()"></div>
      <div x-show="lErr" class="bg-red-50 text-red-600 text-xs rounded-xl px-3 py-2" x-text="lErr"></div>
      <button @click="doLogin()" :disabled="lLoad" class="btn-pri w-full py-2.5 mt-1 disabled:opacity-60">
        <span x-show="!lLoad">Masuk</span><span x-show="lLoad">Memuat…</span>
      </button>
    </div>
  </div>
</div>

{{-- ═══════════════ LAYOUT ═══════════════ --}}
<div x-show="token" x-cloak class="flex h-screen overflow-hidden">

  {{-- Sidebar --}}
  <aside class="w-60 bg-slate-900 flex flex-col flex-shrink-0">
    <div class="px-5 py-4 border-b border-slate-700">
      <p class="text-white font-bold">{{ $appName }}</p>
      <p class="text-slate-400 text-xs">Admin Panel</p>
    </div>
    <nav class="flex-1 p-2 space-y-0.5 overflow-y-auto">
      <template x-for="m in menus" :key="m.id">
        <button @click="nav(m.id)" :class="page===m.id?'on':''" class="sl">
          <span x-html="m.ic" class="w-4 h-4 flex-shrink-0"></span>
          <span x-text="m.label"></span>
        </button>
      </template>
    </nav>
    <div class="p-2 border-t border-slate-700">
      <div class="flex items-center gap-2 px-3 py-2 mb-1">
        <div class="w-7 h-7 rounded-full bg-indigo-500 flex items-center justify-center text-white text-xs font-bold" x-text="(user?.name||'A').charAt(0)"></div>
        <div class="min-w-0"><p class="text-white text-xs font-medium truncate" x-text="user?.name||'Admin'"></p><p class="text-slate-400 text-xs truncate" x-text="user?.email||''"></p></div>
      </div>
      <button @click="doLogout()" class="sl text-red-400 hover:!bg-red-500/10 hover:!text-red-300">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
        Logout
      </button>
    </div>
  </aside>

  {{-- Main --}}
  <main class="flex-1 overflow-y-auto">
    {{-- Topbar --}}
    <div class="bg-white border-b border-slate-200 px-6 py-3.5 flex items-center justify-between sticky top-0 z-10">
      <h2 class="font-semibold text-slate-700" x-text="menus.find(m=>m.id===page)?.label||''"></h2>
      <div class="flex items-center gap-3">
        <span x-show="loading" class="text-xs text-slate-400 animate-pulse">Memuat…</span>
        <transition enter="opacity-0 scale-95" enter-end="opacity-100 scale-100" leave="opacity-100" leave-end="opacity-0 scale-95">
          <span x-show="toast.show" x-text="toast.msg"
            :class="toast.ok?'bg-emerald-100 text-emerald-700':'bg-red-100 text-red-700'"
            class="text-xs font-medium px-3 py-1.5 rounded-full"></span>
        </transition>
      </div>
    </div>

    <div class="p-6 space-y-4">

      {{-- ─── BRANCHES ─── --}}
      <section x-show="page==='branches'" x-cloak>
        <div class="flex items-center justify-between mb-4">
          <h3 class="font-semibold text-lg text-slate-800">Manajemen Branch</h3>
          <button @click="openM('bF',{name:'',code:'',address:'',phone:'',email:'',bank_name:'',bank_account:'',bank_holder:'',tax_id:'',notes:'',is_active:true})" class="btn-pri">+ Tambah Branch</button>
        </div>
        <div class="card overflow-hidden">
          <table>
            <thead><tr><th>Nama</th><th>Kode</th><th>Kontak</th><th>Bank</th><th>Status</th><th>Aksi</th></tr></thead>
            <tbody>
              <template x-for="b in list" :key="b.id">
                <tr>
                  <td>
                    <div class="flex items-center gap-2">
                      <template x-if="b.logo"><img :src="'/storage/'+b.logo" class="w-7 h-7 rounded object-cover"></template>
                      <span class="font-medium" x-text="b.name"></span>
                    </div>
                  </td>
                  <td><code class="bg-slate-100 px-1.5 py-0.5 rounded text-xs" x-text="b.code"></code></td>
                  <td>
                    <p class="text-xs" x-text="b.phone||'-'"></p>
                    <p class="text-xs text-slate-400" x-text="b.email||''"></p>
                  </td>
                  <td>
                    <p class="text-xs" x-text="b.bank_name||'-'"></p>
                    <p class="text-xs text-slate-400 font-mono" x-text="b.bank_account||''"></p>
                  </td>
                  <td>
                    <span class="badge" :class="b.is_active?'bg-emerald-100 text-emerald-700':'bg-red-100 text-red-500'" x-text="b.is_active?'Aktif':'Nonaktif'"></span>
                  </td>
                  <td class="space-x-2">
                    <button @click="openM('bF',{...b})" class="text-indigo-600 hover:underline text-xs">Edit</button>
                    <button @click="toggleBranch(b)" :class="b.is_active?'text-amber-500':'text-emerald-600'" class="hover:underline text-xs" x-text="b.is_active?'Nonaktifkan':'Aktifkan'"></button>
                    <button @click="del('branches',b.id)" class="text-red-500 hover:underline text-xs">Hapus</button>
                  </td>
                </tr>
              </template>
              <tr x-show="!list.length"><td colspan="6" class="text-center text-slate-400 py-8">Belum ada data</td></tr>
            </tbody>
          </table>
        </div>
      </section>

      {{-- ─── BILLING ─── --}}
      <section x-show="page==='billing'" x-cloak>
        <div class="flex items-center justify-between mb-4">
          <h3 class="font-semibold text-lg text-slate-800">Billing Branch</h3>
          <button @click="openM('bilF',{branch_id:'',package_id:'',plan:'monthly',amount:'',billing_date:'',due_date:'',period_start:'',period_end:'',notes:'',payment_method:'',status:'unpaid'})" class="btn-pri">+ Tagihan Baru</button>
        </div>
        {{-- Summary cards --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-4">
          <div class="card p-4">
            <p class="text-xs text-slate-500 mb-1">Total Tagihan</p>
            <p class="text-xl font-bold text-slate-800" x-text="list.length"></p>
          </div>
          <div class="card p-4">
            <p class="text-xs text-slate-500 mb-1">Belum Lunas</p>
            <p class="text-xl font-bold text-amber-600" x-text="list.filter(b=>b.status==='unpaid').length"></p>
          </div>
          <div class="card p-4">
            <p class="text-xs text-slate-500 mb-1">Jatuh Tempo</p>
            <p class="text-xl font-bold text-red-600" x-text="list.filter(b=>b.status==='overdue').length"></p>
          </div>
          <div class="card p-4">
            <p class="text-xs text-slate-500 mb-1">Lunas</p>
            <p class="text-xl font-bold text-emerald-600" x-text="list.filter(b=>b.status==='paid').length"></p>
          </div>
        </div>
        <div class="card overflow-hidden">
          <table>
            <thead><tr><th>Invoice</th><th>Branch</th><th>Package</th><th>Nominal</th><th>Jatuh Tempo</th><th>Status</th><th>Aksi</th></tr></thead>
            <tbody>
              <template x-for="b in list" :key="b.id">
                <tr>
                  <td class="font-mono text-xs" x-text="b.invoice_number"></td>
                  <td class="font-medium" x-text="b.branch?.name||b.branch_id"></td>
                  <td>
                    <span x-show="b.package" class="badge bg-indigo-100 text-indigo-700" x-text="b.package?.name||'-'"></span>
                    <span x-show="!b.package" class="text-slate-400 text-xs" x-text="b.plan"></span>
                  </td>
                  <td class="font-mono text-sm" x-text="'Rp '+Number(b.amount).toLocaleString('id-ID')"></td>
                  <td class="text-sm" x-text="b.due_date"></td>
                  <td>
                    <span class="badge" :class="{
                      'bg-emerald-100 text-emerald-700':b.status==='paid',
                      'bg-red-100 text-red-600':b.status==='overdue',
                      'bg-amber-100 text-amber-700':b.status==='unpaid',
                      'bg-slate-100 text-slate-500':b.status==='cancelled'
                    }" x-text="b.status"></span>
                  </td>
                  <td class="space-x-2">
                    <button @click="openM('bilF',{...b,package_id:b.package_id||'',branch_id:b.branch_id})" class="text-indigo-600 hover:underline text-xs">Edit</button>
                    <button @click="markPaid(b)" x-show="b.status!=='paid'" class="text-emerald-600 hover:underline text-xs">Lunas</button>
                    <button @click="del('billings',b.id)" class="text-red-500 hover:underline text-xs">Hapus</button>
                  </td>
                </tr>
              </template>
              <tr x-show="!list.length"><td colspan="7" class="text-center text-slate-400 py-8">Belum ada data</td></tr>
            </tbody>
          </table>
        </div>
      </section>

      {{-- ─── PACKAGES ─── --}}
      <section x-show="page==='packages'" x-cloak>
        <div class="flex items-center justify-between mb-4">
          <h3 class="font-semibold text-lg text-slate-800">Paket & Harga</h3>
          <button @click="openM('pkgF',{name:'',slug:'',description:'',price_monthly:'',price_quarterly:'',price_yearly:'',features:[],max_users:5,max_branches:1,is_active:true,sort_order:0})" class="btn-pri">+ Tambah Paket</button>
        </div>
        <div class="grid md:grid-cols-3 gap-4">
          <template x-for="p in list" :key="p.id">
            <div class="card p-5" :class="!p.is_active?'opacity-60':''">
              <div class="flex items-start justify-between mb-3">
                <div>
                  <p class="font-bold text-slate-800" x-text="p.name"></p>
                  <code class="text-xs text-slate-400" x-text="p.slug"></code>
                </div>
                <span class="badge" :class="p.is_active?'bg-emerald-100 text-emerald-700':'bg-slate-100 text-slate-500'" x-text="p.is_active?'Aktif':'Nonaktif'"></span>
              </div>
              <p class="text-xs text-slate-500 mb-4" x-text="p.description||'—'"></p>
              <div class="space-y-1 mb-4">
                <div class="flex justify-between text-sm"><span class="text-slate-500">Bulanan</span><span class="font-mono font-semibold" x-text="'Rp '+Number(p.price_monthly).toLocaleString('id-ID')"></span></div>
                <div class="flex justify-between text-sm"><span class="text-slate-500">3 Bulan</span><span class="font-mono" x-text="'Rp '+Number(p.price_quarterly).toLocaleString('id-ID')"></span></div>
                <div class="flex justify-between text-sm"><span class="text-slate-500">Tahunan</span><span class="font-mono" x-text="'Rp '+Number(p.price_yearly).toLocaleString('id-ID')"></span></div>
              </div>
              <div class="border-t border-slate-100 pt-3 mb-3">
                <p class="text-xs text-slate-400 mb-1">Fitur:</p>
                <ul class="space-y-0.5">
                  <template x-for="f in (p.features||[])" :key="f">
                    <li class="text-xs text-slate-600 flex items-center gap-1"><span class="text-emerald-500">✓</span><span x-text="f"></span></li>
                  </template>
                </ul>
                <p class="text-xs text-slate-400 mt-2">Max user: <b x-text="p.max_users"></b> · Max branch: <b x-text="p.max_branches"></b></p>
              </div>
              <div class="flex gap-2">
                <button @click="openM('pkgF',{...p,features:[...(p.features||[])]})" class="btn-sec flex-1 text-xs py-1.5">Edit</button>
                <button @click="togglePkg(p)" :class="p.is_active?'text-amber-500':'text-emerald-600'" class="text-xs px-3 py-1.5 border rounded-xl hover:bg-slate-50" x-text="p.is_active?'Nonaktif':'Aktifkan'"></button>
                <button @click="del('packages',p.id)" class="text-red-500 text-xs px-3 py-1.5 border rounded-xl hover:bg-red-50">Hapus</button>
              </div>
            </div>
          </template>
          <div x-show="!list.length" class="card p-8 text-center text-slate-400 col-span-3">Belum ada paket</div>
        </div>
      </section>

      {{-- ─── USERS ─── --}}
      <section x-show="page==='users'" x-cloak>
        <div class="flex items-center justify-between mb-4">
          <h3 class="font-semibold text-lg text-slate-800">Manajemen User</h3>
          <button @click="openM('uF',{name:'',email:'',password:'',role:'branch_admin',branch_id:''})" class="btn-pri">+ Tambah User</button>
        </div>
        <div class="card overflow-hidden">
          <table>
            <thead><tr><th>Nama</th><th>Email</th><th>Branch</th><th>Role</th><th>Aksi</th></tr></thead>
            <tbody>
              <template x-for="u in list" :key="u.id">
                <tr>
                  <td class="font-medium" x-text="u.name"></td>
                  <td class="text-slate-500" x-text="u.email"></td>
                  <td>
                    <template x-for="b in (u.branches||[])" :key="b.id">
                      <span class="badge bg-indigo-100 text-indigo-700 mr-1" x-text="b.name"></span>
                    </template>
                  </td>
                  <td>
                    <template x-for="b in (u.branches||[])" :key="b.id">
                      <span class="badge mr-1" :class="b.pivot?.role==='super_admin'?'bg-purple-100 text-purple-700':b.pivot?.role==='branch_admin'?'bg-blue-100 text-blue-700':'bg-green-100 text-green-700'" x-text="b.pivot?.role||'-'"></span>
                    </template>
                  </td>
                  <td class="space-x-2">
                    <button @click="openM('uF',{...u,password:'',branch_id:u.branches?.[0]?.id||'',role:u.branches?.[0]?.pivot?.role||'branch_admin'})" class="text-indigo-600 hover:underline text-xs">Edit</button>
                    <button @click="del('users',u.id)" class="text-red-500 hover:underline text-xs">Hapus</button>
                  </td>
                </tr>
              </template>
              <tr x-show="!list.length"><td colspan="5" class="text-center text-slate-400 py-8">Belum ada data</td></tr>
            </tbody>
          </table>
        </div>
      </section>

      {{-- ─── NOTIFICATIONS ─── --}}
      <section x-show="page==='notifications'" x-cloak>
        <div class="flex items-center justify-between mb-4">
          <h3 class="font-semibold text-lg text-slate-800">Notifikasi & Pengumuman</h3>
          <button @click="openM('nF',{title:'',body:'',type:'announcement',is_broadcast:true,branch_ids:[],action_url:'',is_draft:false})" class="btn-pri">+ Buat Notifikasi</button>
        </div>

        {{-- Tabs --}}
        <div class="flex gap-2 mb-4">
          <button @click="notifTab='all';loadPage()" :class="notifTab==='all'?'bg-indigo-600 text-white':'bg-white text-slate-600'" class="text-xs px-3 py-1.5 rounded-full border font-medium">Semua</button>
          <button @click="notifTab='sent';loadPage()" :class="notifTab==='sent'?'bg-indigo-600 text-white':'bg-white text-slate-600'" class="text-xs px-3 py-1.5 rounded-full border font-medium">Terkirim</button>
          <button @click="notifTab='draft';loadPage()" :class="notifTab==='draft'?'bg-indigo-600 text-white':'bg-white text-slate-600'" class="text-xs px-3 py-1.5 rounded-full border font-medium">Draft</button>
        </div>

        <div class="space-y-3">
          <template x-for="n in list" :key="n.id">
            <div class="card p-4 flex items-start gap-4">
              <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0 text-xl"
                :class="n.type==='billing'?'bg-amber-100':n.type==='alert'?'bg-red-100':n.type==='update'?'bg-emerald-100':'bg-blue-100'">
                <span x-text="n.type==='billing'?'💳':n.type==='alert'?'🚨':n.type==='update'?'🔄':'📢'"></span>
              </div>
              <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2 flex-wrap mb-1">
                  <span class="font-semibold text-sm" x-text="n.title"></span>
                  <span class="badge"
                    :class="n.type==='announcement'?'bg-blue-100 text-blue-700':n.type==='billing'?'bg-amber-100 text-amber-700':n.type==='alert'?'bg-red-100 text-red-700':'bg-emerald-100 text-emerald-700'"
                    x-text="n.type==='announcement'?'Pengumuman':n.type==='billing'?'Billing':n.type==='alert'?'Alert':'Update'"></span>
                  <span x-show="n.is_broadcast" class="badge bg-purple-100 text-purple-700">📡 Broadcast</span>
                  <span x-show="n.is_draft" class="badge bg-slate-100 text-slate-500">📝 Draft</span>
                </div>
                <p class="text-sm text-slate-500 mb-2 line-clamp-2" x-text="n.body"></p>
                <div class="flex flex-wrap gap-1 mb-1">
                  <template x-for="b in (n.branches||[])" :key="b.id">
                    <span class="badge bg-indigo-50 text-indigo-600" x-text="b.name"></span>
                  </template>
                </div>
                <p class="text-xs text-slate-400" x-text="n.sent_at?'✅ Terkirim: '+new Date(n.sent_at).toLocaleString('id-ID'):(n.scheduled_at?'🕐 Dijadwal: '+n.scheduled_at:'⏸ Belum dikirim')"></p>
              </div>
              <div class="flex gap-2 flex-shrink-0 flex-col items-end">
                <button @click="openM('nF',{id:n.id,title:n.title,body:n.body,type:n.type,is_broadcast:n.is_broadcast,branch_ids:(n.branches||[]).map(b=>b.id),action_url:n.action_url||'',is_draft:n.is_draft})" class="text-indigo-600 hover:underline text-xs font-medium">Edit</button>
                <button x-show="n.is_draft" @click="publishNotif(n)" class="text-emerald-600 hover:underline text-xs font-medium">Kirim</button>
                <button @click="del('notifications',n.id)" class="text-red-500 hover:underline text-xs font-medium">Hapus</button>
              </div>
            </div>
          </template>
          <div x-show="!list.length" class="card p-8 text-center text-slate-400">Belum ada notifikasi</div>
        </div>
      </section>

      {{-- ─── SMTP ─── --}}
      <section x-show="page==='smtp'" x-cloak>
        <div class="flex items-center justify-between mb-4">
          <h3 class="font-semibold text-lg text-slate-800">Pengaturan SMTP</h3>
          <button @click="openM('sF',{name:'',host:'',port:587,username:'',password:'',encryption:'tls',from_address:'',from_name:'',is_active:true})" class="btn-pri">+ Tambah SMTP</button>
        </div>
        <div class="space-y-3">
          <template x-for="s in list" :key="s.id">
            <div class="card p-4">
              <div class="flex items-center justify-between mb-3">
                <div class="flex items-center gap-2">
                  <span class="font-semibold" x-text="s.name"></span>
                  <span x-show="s.is_active" class="badge bg-indigo-100 text-indigo-700">Default</span>
                </div>
                <div class="flex gap-2">
                  <button @click="testSmtp(s)" class="text-xs bg-emerald-100 text-emerald-700 hover:bg-emerald-200 px-3 py-1 rounded-full">Test Kirim</button>
                  <button @click="openM('sF',{...s})" class="text-indigo-600 hover:underline text-xs">Edit</button>
                  <button @click="del('smtp',s.id)" class="text-red-500 hover:underline text-xs">Hapus</button>
                </div>
              </div>
              <div class="grid4 grid grid-cols-2 md:grid-cols-4 gap-3 text-sm">
                <div><p class="text-xs text-slate-400">Host</p><p class="font-mono text-xs" x-text="s.host"></p></div>
                <div><p class="text-xs text-slate-400">Port</p><p class="font-mono text-xs" x-text="s.port"></p></div>
                <div><p class="text-xs text-slate-400">Enkripsi</p><p class="text-xs capitalize" x-text="s.encryption"></p></div>
                <div><p class="text-xs text-slate-400">From</p><p class="text-xs truncate" x-text="s.from_address"></p></div>
              </div>
            </div>
          </template>
          <div x-show="!list.length" class="card p-8 text-center text-slate-400">Belum ada SMTP</div>
        </div>

        <div class="flex items-center justify-between mt-8 mb-4">
          <h3 class="font-semibold text-lg text-slate-800">Jadwal Laporan Harian</h3>
          <button @click="openM('schF',{branch_id:'',recipient_email:'',send_at:'07:00',is_active:true})" class="btn-sec">+ Tambah Jadwal</button>
        </div>
        <div class="card overflow-hidden">
          <table>
            <thead><tr><th>Branch</th><th>Email Tujuan</th><th>Jam Kirim</th><th>Status</th><th>Aksi</th></tr></thead>
            <tbody>
              <template x-for="r in schedules" :key="r.id||r.branch_id">
                <tr>
                  <td class="font-medium" x-text="r.branch?.name||r.branch_id"></td>
                  <td class="text-slate-500" x-text="r.recipient_email||r.recipients"></td>
                  <td class="font-mono text-xs" x-text="r.send_at||'-'"></td>
                  <td><span class="badge" :class="r.is_active||r.enabled?'bg-emerald-100 text-emerald-700':'bg-slate-100 text-slate-500'" x-text="r.is_active||r.enabled?'Aktif':'Nonaktif'"></span></td>
                  <td><button @click="openM('schF',{...r,branch_id:r.branch_id})" class="text-indigo-600 hover:underline text-xs">Edit</button></td>
                </tr>
              </template>
              <tr x-show="!schedules.length"><td colspan="5" class="text-center text-slate-400 py-8">Belum ada jadwal</td></tr>
            </tbody>
          </table>
        </div>
      </section>

    </div>{{-- /p-6 --}}
  </main>
</div>{{-- /layout --}}

{{-- ═══════════════ MODALS ═══════════════ --}}

{{-- Branch Form --}}
<div x-show="modal==='bF'" x-cloak class="modal-bg" @click.self="modal=null">
  <div class="modal-box" @click.stop>
    <h3 class="font-bold text-lg mb-4" x-text="form.id?'Edit Branch':'Tambah Branch'"></h3>
    <div class="space-y-3">
      <div class="grid2">
        <div><label class="lbl">Nama *</label><input x-model="form.name" class="inp" placeholder="Toko Pusat"></div>
        <div><label class="lbl">Kode * (unik)</label><input x-model="form.code" class="inp font-mono" placeholder="PST"></div>
      </div>
      <div><label class="lbl">Alamat</label><textarea x-model="form.address" rows="2" class="inp"></textarea></div>
      <div class="grid2">
        <div><label class="lbl">Telepon</label><input x-model="form.phone" class="inp"></div>
        <div><label class="lbl">Email</label><input x-model="form.email" type="email" class="inp"></div>
      </div>
      <div class="grid2">
        <div><label class="lbl">Nama Bank</label><input x-model="form.bank_name" class="inp" placeholder="BCA"></div>
        <div><label class="lbl">No. Rekening</label><input x-model="form.bank_account" class="inp font-mono"></div>
      </div>
      <div class="grid2">
        <div><label class="lbl">Atas Nama Rekening</label><input x-model="form.bank_holder" class="inp"></div>
        <div><label class="lbl">NPWP</label><input x-model="form.tax_id" class="inp font-mono"></div>
      </div>
      <div><label class="lbl">Catatan Internal</label><textarea x-model="form.notes" rows="2" class="inp"></textarea></div>
      <label class="flex items-center gap-2 cursor-pointer">
        <input type="checkbox" x-model="form.is_active" class="rounded">
        <span class="text-sm text-slate-600">Branch Aktif</span>
      </label>
    </div>
    <div class="flex gap-3 mt-5">
      <button @click="modal=null" class="btn-sec flex-1">Batal</button>
      <button @click="saveBranch()" class="btn-pri flex-1">Simpan</button>
    </div>
  </div>
</div>

{{-- Billing Form --}}
<div x-show="modal==='bilF'" x-cloak class="modal-bg" @click.self="modal=null">
  <div class="modal-box" @click.stop>
    <h3 class="font-bold text-lg mb-4" x-text="form.id?'Edit Tagihan':'Tagihan Baru'"></h3>
    <div class="space-y-3">
      <div><label class="lbl">Branch *</label>
        <select x-model="form.branch_id" class="inp">
          <option value="">-- Pilih Branch --</option>
          <template x-for="b in branches" :key="b.id"><option :value="b.id" x-text="b.name"></option></template>
        </select>
      </div>
      <div class="grid2">
        <div><label class="lbl">Paket</label>
          <select x-model="form.package_id" class="inp" @change="autoFillAmount()">
            <option value="">-- Tanpa Paket --</option>
            <template x-for="p in packages" :key="p.id"><option :value="p.id" x-text="p.name"></option></template>
          </select>
        </div>
        <div><label class="lbl">Periode</label>
          <select x-model="form.plan" class="inp" @change="autoFillAmount()">
            <option value="monthly">Bulanan</option>
            <option value="quarterly">3 Bulan</option>
            <option value="yearly">Tahunan</option>
            <option value="lifetime">Lifetime</option>
            <option value="custom">Custom</option>
          </select>
        </div>
      </div>
      <div><label class="lbl">Nominal (Rp) *</label><input x-model="form.amount" type="number" class="inp"></div>
      <div class="grid2">
        <div><label class="lbl">Tgl Tagihan</label><input x-model="form.billing_date" type="date" class="inp"></div>
        <div><label class="lbl">Jatuh Tempo *</label><input x-model="form.due_date" type="date" class="inp"></div>
      </div>
      <div class="grid2">
        <div><label class="lbl">Periode Mulai</label><input x-model="form.period_start" type="date" class="inp"></div>
        <div><label class="lbl">Periode Selesai</label><input x-model="form.period_end" type="date" class="inp"></div>
      </div>
      <div class="grid2">
        <div><label class="lbl">Metode Bayar</label>
          <select x-model="form.payment_method" class="inp">
            <option value="">-- Pilih --</option>
            <option value="transfer">Transfer Bank</option>
            <option value="qris">QRIS</option>
            <option value="cash">Cash</option>
            <option value="other">Lainnya</option>
          </select>
        </div>
        <div><label class="lbl">Status</label>
          <select x-model="form.status" class="inp">
            <option value="unpaid">Unpaid</option>
            <option value="paid">Paid</option>
            <option value="overdue">Overdue</option>
            <option value="cancelled">Cancelled</option>
          </select>
        </div>
      </div>
      <div><label class="lbl">Catatan</label><textarea x-model="form.notes" rows="2" class="inp"></textarea></div>
    </div>
    <div class="flex gap-3 mt-5">
      <button @click="modal=null" class="btn-sec flex-1">Batal</button>
      <button @click="saveBilling()" class="btn-pri flex-1">Simpan</button>
    </div>
  </div>
</div>

{{-- Package Form --}}
<div x-show="modal==='pkgF'" x-cloak class="modal-bg" @click.self="modal=null">
  <div class="modal-box" @click.stop>
    <h3 class="font-bold text-lg mb-4" x-text="form.id?'Edit Paket':'Tambah Paket'"></h3>
    <div class="space-y-3">
      <div class="grid2">
        <div><label class="lbl">Nama *</label><input x-model="form.name" class="inp" placeholder="Basic"></div>
        <div><label class="lbl">Slug</label><input x-model="form.slug" class="inp font-mono" placeholder="basic"></div>
      </div>
      <div><label class="lbl">Deskripsi</label><textarea x-model="form.description" rows="2" class="inp"></textarea></div>
      <div class="grid3">
        <div><label class="lbl">Harga/Bulan (Rp) *</label><input x-model="form.price_monthly" type="number" class="inp" @input="calcPrices()"></div>
        <div><label class="lbl">Harga/3 Bulan</label><input x-model="form.price_quarterly" type="number" class="inp"></div>
        <div><label class="lbl">Harga/Tahun</label><input x-model="form.price_yearly" type="number" class="inp"></div>
      </div>
      <div><label class="lbl">Fitur (satu per baris)</label>
        <textarea :value="(form.features||[]).join('\n')"
          @input="form.features = $event.target.value.split('\n').filter(f=>f.trim())"
          rows="4" class="inp" placeholder="Unlimited produk&#10;Laporan harian&#10;Multi kasir"></textarea>
      </div>
      <div class="grid2">
        <div><label class="lbl">Max User</label><input x-model="form.max_users" type="number" class="inp"></div>
        <div><label class="lbl">Max Branch</label><input x-model="form.max_branches" type="number" class="inp"></div>
      </div>
      <div class="grid2">
        <div><label class="lbl">Urutan Tampil</label><input x-model="form.sort_order" type="number" class="inp"></div>
        <div class="flex items-end pb-1">
          <label class="flex items-center gap-2 cursor-pointer">
            <input type="checkbox" x-model="form.is_active" class="rounded">
            <span class="text-sm text-slate-600">Paket Aktif</span>
          </label>
        </div>
      </div>
    </div>
    <div class="flex gap-3 mt-5">
      <button @click="modal=null" class="btn-sec flex-1">Batal</button>
      <button @click="savePkg()" class="btn-pri flex-1">Simpan</button>
    </div>
  </div>
</div>

{{-- User Form --}}
<div x-show="modal==='uF'" x-cloak class="modal-bg" @click.self="modal=null">
  <div class="modal-box" @click.stop>
    <h3 class="font-bold text-lg mb-4" x-text="form.id?'Edit User':'Tambah User'"></h3>
    <div class="space-y-3">
      <div class="grid2">
        <div><label class="lbl">Nama *</label><input x-model="form.name" class="inp"></div>
        <div><label class="lbl">Email *</label><input x-model="form.email" type="email" class="inp"></div>
      </div>
      <div><label class="lbl">Password <span x-text="form.id?'(kosongkan jika tidak diubah)':'*'"></span></label>
        <input x-model="form.password" type="password" class="inp">
      </div>
      <div class="grid2">
        <div><label class="lbl">Branch</label>
          <select x-model="form.branch_id" class="inp">
            <option value="">-- Pilih Branch --</option>
            <template x-for="b in branches" :key="b.id"><option :value="b.id" x-text="b.name"></option></template>
          </select>
        </div>
        <div><label class="lbl">Role</label>
          <select x-model="form.role" class="inp">
            <option value="super_admin">super_admin</option>
            <option value="branch_admin">branch_admin</option>
            <option value="cashier">cashier</option>
          </select>
        </div>
      </div>
    </div>
    <div class="flex gap-3 mt-5">
      <button @click="modal=null" class="btn-sec flex-1">Batal</button>
      <button @click="saveUser()" class="btn-pri flex-1">Simpan</button>
    </div>
  </div>
</div>

{{-- Notification Form --}}
<div x-show="modal==='nF'" x-cloak class="modal-bg" @click.self="modal=null">
  <div class="modal-box" @click.stop>
    <h3 class="font-bold text-lg mb-4" x-text="form.id?'Edit Notifikasi':'Buat Notifikasi'"></h3>
    <div class="space-y-3">
      <div><label class="lbl">Judul *</label><input x-model="form.title" class="inp"></div>
      <div><label class="lbl">Isi Pesan *</label><textarea x-model="form.body" rows="3" class="inp"></textarea></div>
      <div class="grid2">
        <div><label class="lbl">Tipe</label>
          <select x-model="form.type" class="inp">
            <option value="announcement">Pengumuman</option>
            <option value="update">Update</option>
            <option value="billing">Billing</option>
            <option value="alert">Alert</option>
          </select>
        </div>
        <div><label class="lbl">URL Aksi (opsional)</label><input x-model="form.action_url" class="inp" placeholder="https://..."></div>
      </div>
      <div>
        <label class="flex items-center gap-2 cursor-pointer mb-2">
          <input type="checkbox" x-model="form.is_broadcast" class="rounded">
          <span class="text-sm text-slate-600">Kirim ke <b>Semua Branch</b> (broadcast)</span>
        </label>
        <div x-show="!form.is_broadcast">
          <label class="lbl mb-2">Pilih Branch Tujuan</label>
          <div class="border border-slate-200 rounded-xl p-3 space-y-1.5 max-h-36 overflow-y-auto">
            <template x-for="b in branches" :key="b.id">
              <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" :value="b.id" :checked="form.branch_ids&&form.branch_ids.includes(b.id)" @change="toggleBranchId(b.id)" class="rounded">
                <span class="text-sm" x-text="b.name"></span>
              </label>
            </template>
          </div>
        </div>
      </div>
      <label class="flex items-center gap-2 cursor-pointer">
        <input type="checkbox" x-model="form.is_draft" class="rounded">
        <span class="text-sm text-slate-600">Simpan sebagai Draft (belum dikirim)</span>
      </label>
    </div>
    <div class="flex gap-3 mt-5">
      <button @click="modal=null" class="btn-sec flex-1">Batal</button>
      <button @click="saveNotif()" class="btn-pri flex-1" x-text="form.is_draft?'Simpan Draft':'Kirim Sekarang'"></button>
    </div>
  </div>
</div>

{{-- SMTP Form --}}
<div x-show="modal==='sF'" x-cloak class="modal-bg" @click.self="modal=null">
  <div class="modal-box" @click.stop>
    <h3 class="font-bold text-lg mb-4" x-text="form.id?'Edit SMTP':'Tambah SMTP'"></h3>
    <div class="space-y-3">
      <div><label class="lbl">Nama Konfigurasi *</label><input x-model="form.name" class="inp" placeholder="Gmail Produksi"></div>
      <div class="grid3">
        <div class="col-span-2"><label class="lbl">Host *</label><input x-model="form.host" class="inp font-mono" placeholder="smtp.gmail.com"></div>
        <div><label class="lbl">Port</label><input x-model="form.port" type="number" class="inp font-mono"></div>
      </div>
      <div class="grid2">
        <div><label class="lbl">Username</label><input x-model="form.username" class="inp"></div>
        <div><label class="lbl">Password</label><input x-model="form.password" type="password" class="inp"></div>
      </div>
      <div class="grid2">
        <div><label class="lbl">Enkripsi</label>
          <select x-model="form.encryption" class="inp">
            <option value="tls">TLS</option><option value="ssl">SSL</option><option value="none">None</option>
          </select>
        </div>
        <div><label class="lbl">From Name</label><input x-model="form.from_name" class="inp"></div>
      </div>
      <div><label class="lbl">From Address</label><input x-model="form.from_address" type="email" class="inp"></div>
      <label class="flex items-center gap-2 cursor-pointer">
        <input type="checkbox" x-model="form.is_active" class="rounded">
        <span class="text-sm text-slate-600">Set sebagai SMTP aktif / default</span>
      </label>
    </div>
    <div class="flex gap-3 mt-5">
      <button @click="modal=null" class="btn-sec flex-1">Batal</button>
      <button @click="saveSmtp()" class="btn-pri flex-1">Simpan</button>
    </div>
  </div>
</div>

{{-- Schedule Form --}}
<div x-show="modal==='schF'" x-cloak class="modal-bg" @click.self="modal=null">
  <div class="modal-box" @click.stop>
    <h3 class="font-bold text-lg mb-4">Jadwal Laporan Harian</h3>
    <div class="space-y-3">
      <div><label class="lbl">Branch *</label>
        <select x-model="form.branch_id" class="inp">
          <option value="">-- Pilih Branch --</option>
          <template x-for="b in branches" :key="b.id"><option :value="b.id" x-text="b.name"></option></template>
        </select>
      </div>
      <div><label class="lbl">Email Tujuan *</label><input x-model="form.recipient_email" type="email" class="inp"></div>
      <div><label class="lbl">Jam Kirim</label><input x-model="form.send_at" type="time" class="inp"></div>
      <label class="flex items-center gap-2 cursor-pointer">
        <input type="checkbox" x-model="form.is_active" class="rounded">
        <span class="text-sm text-slate-600">Aktifkan jadwal</span>
      </label>
    </div>
    <div class="flex gap-3 mt-5">
      <button @click="modal=null" class="btn-sec flex-1">Batal</button>
      <button @click="saveSch()" class="btn-pri flex-1">Simpan</button>
    </div>
  </div>
</div>

{{-- ═══════════════ ALPINE APP ═══════════════ --}}
<script>
function adminApp(apiBase) {
  return {
    apiBase,
    token: localStorage.getItem('kinaya_admin_token') || '',
    user: JSON.parse(localStorage.getItem('kinaya_admin_user') || 'null'),
    page: 'branches',
    modal: null,
    form: {},
    list: [],
    branches: [],
    packages: [],
    schedules: [],
    loading: false,
    lLoad: false,
    lErr: '',
    lf: { email: 'admin@kinaya.test', password: '' },
    toast: { show: false, msg: '', ok: true },
    notifTab: 'all',

    menus: [
      { id:'branches',      label:'Branch',         ic:'<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-2 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>' },
      { id:'billing',       label:'Billing',        ic:'<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>' },
      { id:'packages',      label:'Paket & Harga',  ic:'<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>' },
      { id:'users',         label:'Users',          ic:'<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>' },
      { id:'notifications', label:'Notifikasi',     ic:'<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>' },
      { id:'smtp',          label:'SMTP & Jadwal',  ic:'<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>' },
    ],

    init() {
      if (this.token) { this.loadBranches(); this.loadPackages(); this.loadPage(); }
    },

    showToast(msg, ok=true) {
      this.toast = { show:true, msg, ok };
      setTimeout(()=>this.toast.show=false, 3500);
    },

    async req(method, path, body=null) {
      const h = { 'Accept':'application/json','Content-Type':'application/json' };
      if (this.token) h['Authorization'] = 'Bearer '+this.token;
      const res = await fetch(this.apiBase+path, { method, headers:h, body:body?JSON.stringify(body):null });
      const data = await res.json().catch(()=>({}));
      if (!res.ok) throw data;
      return data;
    },

    // ── Auth ──
    async doLogin() {
      this.lLoad=true; this.lErr='';
      try {
        const data = await this.req('POST','/login', this.lf);
        const role = data.branches?.[0]?.pivot?.role;
        if (role !== 'super_admin') { this.lErr='Akses ditolak. Hanya super_admin.'; this.lLoad=false; return; }
        this.token = data.token;
        this.user  = data.user;
        localStorage.setItem('kinaya_admin_token', data.token);
        localStorage.setItem('kinaya_admin_user', JSON.stringify(data.user));
        await this.loadBranches();
        await this.loadPackages();
        this.loadPage();
      } catch(e) { this.lErr = e?.message || e?.email?.[0] || 'Login gagal'; }
      this.lLoad=false;
    },

    doLogout() {
      this.token=''; this.user=null;
      localStorage.removeItem('kinaya_admin_token');
      localStorage.removeItem('kinaya_admin_user');
      this.req('POST','/logout').catch(()=>{});
    },

    nav(id) { this.page=id; this.loadPage(); },

    // ── Loaders ──
    async loadBranches() {
      try { const d = await this.req('GET','/admin/branches'); this.branches = d.data||d; } catch(_){}
    },
    async loadPackages() {
      try { const d = await this.req('GET','/admin/packages'); this.packages = d.data||d; } catch(_){}
    },

    async loadPage() {
      this.loading=true; this.list=[];
      const map = {
        users:'/admin/users', branches:'/admin/branches',
        billing:'/admin/billings', notifications:'/admin/notifications',
        smtp:'/admin/smtp', packages:'/admin/packages',
      };
      try {
        let url = map[this.page];
        if (!url) { this.loading=false; return; }
        if (this.page==='notifications') {
          if (this.notifTab==='sent')  url+='?is_draft=0';
          if (this.notifTab==='draft') url+='?is_draft=1';
        }
        const d = await this.req('GET', url);
        this.list = d.data||d;
        if (this.page==='smtp') {
          const sch = await this.req('GET','/admin/report-schedules');
          this.schedules = sch.data||sch;
        }
      } catch(e) {
        if (e?.message?.includes('Unauthenticated') || e?.message?.includes('401')) { this.doLogout(); }
        else this.showToast('Gagal memuat data','false');
      }
      this.loading=false;
    },

    openM(name, data) { this.form={...data}; this.modal=name; },

    // ── Branch ──
    async saveBranch() {
      try {
        const body = {name:this.form.name,code:this.form.code,address:this.form.address,phone:this.form.phone,
          email:this.form.email,bank_name:this.form.bank_name,bank_account:this.form.bank_account,
          bank_holder:this.form.bank_holder,tax_id:this.form.tax_id,notes:this.form.notes,is_active:this.form.is_active};
        if (this.form.id) await this.req('PUT','/admin/branches/'+this.form.id, body);
        else await this.req('POST','/admin/branches', body);
        this.modal=null; this.loadPage(); this.loadBranches();
        this.showToast('Branch disimpan ✅');
      } catch(e) { this.showToast(JSON.stringify(e?.errors||e?.message||'Gagal'),false); }
    },

    async toggleBranch(b) {
      try { await this.req('PATCH','/admin/branches/'+b.id+'/toggle'); this.loadPage(); this.showToast('Status branch diperbarui'); }
      catch(e) { this.showToast('Gagal',false); }
    },

    // ── Billing ──
    autoFillAmount() {
      if (!this.form.package_id || !this.form.plan) return;
      const pkg = this.packages.find(p=>p.id==this.form.package_id);
      if (!pkg) return;
      const map = { monthly:pkg.price_monthly, quarterly:pkg.price_quarterly, yearly:pkg.price_yearly };
      if (map[this.form.plan] !== undefined) this.form.amount = map[this.form.plan];
    },

    async saveBilling() {
      try {
        const body = {branch_id:this.form.branch_id,package_id:this.form.package_id||null,
          plan:this.form.plan,amount:this.form.amount,billing_date:this.form.billing_date,
          due_date:this.form.due_date,period_start:this.form.period_start,period_end:this.form.period_end,
          notes:this.form.notes,payment_method:this.form.payment_method,status:this.form.status};
        if (this.form.id) await this.req('PUT','/admin/billings/'+this.form.id, body);
        else await this.req('POST','/admin/billings', body);
        this.modal=null; this.loadPage();
        this.showToast('Tagihan disimpan ✅');
      } catch(e) { this.showToast(JSON.stringify(e?.errors||e?.message||'Gagal'),false); }
    },

    async markPaid(b) {
      try { await this.req('PATCH','/admin/billings/'+b.id+'/pay'); this.loadPage(); this.showToast('Tagihan dilunasi ✅'); }
      catch(e) { this.showToast('Gagal',false); }
    },

    // ── Package ──
    calcPrices() {
      if (!this.form.price_monthly) return;
      const m = parseFloat(this.form.price_monthly);
      if (!this.form.price_quarterly) this.form.price_quarterly = Math.round(m*3*0.95);
      if (!this.form.price_yearly)    this.form.price_yearly    = Math.round(m*12*0.85);
    },

    async savePkg() {
      try {
        const body = {name:this.form.name,slug:this.form.slug||undefined,description:this.form.description,
          price_monthly:this.form.price_monthly,price_quarterly:this.form.price_quarterly,
          price_yearly:this.form.price_yearly,features:this.form.features||[],
          max_users:this.form.max_users,max_branches:this.form.max_branches,
          is_active:this.form.is_active,sort_order:this.form.sort_order||0};
        if (this.form.id) await this.req('PUT','/admin/packages/'+this.form.id, body);
        else await this.req('POST','/admin/packages', body);
        this.modal=null; this.loadPage(); this.loadPackages();
        this.showToast('Paket disimpan ✅');
      } catch(e) { this.showToast(JSON.stringify(e?.errors||e?.message||'Gagal'),false); }
    },

    async togglePkg(p) {
      try { await this.req('PATCH','/admin/packages/'+p.id+'/toggle'); this.loadPage(); this.loadPackages(); this.showToast('Status paket diperbarui'); }
      catch(e) { this.showToast('Gagal',false); }
    },

    // ── Users ──
    async saveUser() {
      try {
        const body = {name:this.form.name,email:this.form.email};
        if (this.form.password) body.password = this.form.password;
        if (this.form.branch_id) { body.branch_id=this.form.branch_id; body.role=this.form.role||'branch_admin'; }
        if (this.form.id) await this.req('PUT','/admin/users/'+this.form.id, body);
        else await this.req('POST','/admin/users', body);
        this.modal=null; this.loadPage();
        this.showToast(this.form.id?'User diperbarui ✅':'User ditambahkan ✅');
      } catch(e) { this.showToast(JSON.stringify(e?.errors||e?.message||'Gagal'),false); }
    },

    // ── Notifications ──
    toggleBranchId(id) {
      if (!this.form.branch_ids) this.form.branch_ids=[];
      const i = this.form.branch_ids.indexOf(id);
      if (i===-1) this.form.branch_ids.push(id); else this.form.branch_ids.splice(i,1);
    },

    async saveNotif() {
      try {
        const body = {title:this.form.title,body:this.form.body,type:this.form.type,
          is_broadcast:this.form.is_broadcast,branch_ids:this.form.branch_ids||[],
          action_url:this.form.action_url||null,is_draft:this.form.is_draft||false};
        if (this.form.id) await this.req('PUT','/admin/notifications/'+this.form.id, body);
        else await this.req('POST','/admin/notifications', body);
        this.modal=null; this.loadPage();
        this.showToast(body.is_draft?'Draft disimpan':'Notifikasi dikirim ✅');
      } catch(e) { this.showToast(JSON.stringify(e?.errors||e?.message||'Gagal'),false); }
    },

    async publishNotif(n) {
      try { await this.req('PUT','/admin/notifications/'+n.id,{is_draft:false}); this.loadPage(); this.showToast('Notifikasi dikirim ✅'); }
      catch(e) { this.showToast('Gagal',false); }
    },

    // ── SMTP ──
    async saveSmtp() {
      try {
        const body = {name:this.form.name,host:this.form.host,port:this.form.port,username:this.form.username,
          password:this.form.password,encryption:this.form.encryption,
          from_address:this.form.from_address,from_name:this.form.from_name,is_active:this.form.is_active};
        if (this.form.id) await this.req('PUT','/admin/smtp/'+this.form.id, body);
        else await this.req('POST','/admin/smtp', body);
        this.modal=null; this.loadPage();
        this.showToast('SMTP disimpan ✅');
      } catch(e) { this.showToast(JSON.stringify(e?.errors||e?.message||'Gagal'),false); }
    },

    async testSmtp(s) {
      const email = prompt('Kirim email test ke:');
      if (!email) return;
      try { await this.req('POST','/admin/smtp/'+s.id+'/test',{to:email}); this.showToast('Test email terkirim ke '+email+' ✅'); }
      catch(e) { this.showToast(e?.message||'Gagal',false); }
    },

    // ── Schedule ──
    async saveSch() {
      try {
        const body = {recipient_email:this.form.recipient_email,send_at:this.form.send_at,is_active:this.form.is_active};
        await this.req('PUT','/admin/report-schedules/'+this.form.branch_id, body);
        this.modal=null; this.loadPage();
        this.showToast('Jadwal disimpan ✅');
      } catch(e) { this.showToast(JSON.stringify(e?.errors||e?.message||'Gagal'),false); }
    },

    // ── Generic Delete ──
    async del(resource, id) {
      if (!confirm('Hapus data ini?')) return;
      try { await this.req('DELETE','/admin/'+resource+'/'+id); this.loadPage(); this.showToast('Dihapus'); }
      catch(e) { this.showToast(e?.message||'Gagal hapus',false); }
    },
  }
}
</script>
</body>
</html>
