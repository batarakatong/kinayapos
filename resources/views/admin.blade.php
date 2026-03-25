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
  [x-cloak] { display: none !important; }
  body { font-family: 'Inter', sans-serif; }
  .sidebar-link { @apply flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-medium transition-all; }
  .sidebar-link.active { @apply bg-indigo-600 text-white; }
  .sidebar-link:not(.active) { @apply text-slate-300 hover:bg-slate-700 hover:text-white; }
</style>
</head>
<body class="bg-slate-100 text-slate-800" x-data="adminApp('{{ $apiBase }}')" x-init="init()">

<!-- Login Screen -->
<div x-show="!token" x-cloak class="min-h-screen flex items-center justify-center bg-gradient-to-br from-indigo-600 to-emerald-500">
  <div class="bg-white rounded-2xl shadow-2xl p-8 w-full max-w-sm">
    <div class="text-center mb-6">
      <div class="w-16 h-16 bg-indigo-600 rounded-2xl flex items-center justify-center mx-auto mb-3">
        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
      </div>
      <h1 class="text-2xl font-bold text-slate-800">{{ $appName }}</h1>
      <p class="text-slate-500 text-sm mt-1">Admin Panel — Super Admin Only</p>
    </div>
    <div class="space-y-4">
      <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">Email</label>
        <input x-model="loginForm.email" type="email" placeholder="admin@kinaya.test"
          class="w-full border border-slate-300 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
      </div>
      <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">Password</label>
        <input x-model="loginForm.password" type="password" placeholder="••••••••"
          @keydown.enter="doLogin()"
          class="w-full border border-slate-300 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
      </div>
      <div x-show="loginError" class="bg-red-50 text-red-600 text-sm rounded-xl px-4 py-2.5" x-text="loginError"></div>
      <button @click="doLogin()" :disabled="loginLoading"
        class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2.5 rounded-xl transition-all disabled:opacity-60">
        <span x-show="!loginLoading">Masuk</span>
        <span x-show="loginLoading">Memuat…</span>
      </button>
    </div>
  </div>
</div>

<!-- Admin Layout -->
<div x-show="token" x-cloak class="flex h-screen overflow-hidden">

  <!-- Sidebar -->
  <aside class="w-64 bg-slate-800 flex flex-col flex-shrink-0">
    <div class="px-6 py-5 border-b border-slate-700">
      <h1 class="text-white font-bold text-lg">{{ $appName }}</h1>
      <p class="text-slate-400 text-xs mt-0.5">Admin Panel</p>
    </div>
    <nav class="flex-1 p-3 space-y-1 overflow-y-auto">
      <template x-for="menu in menus" :key="menu.id">
        <button @click="page = menu.id; loadPage()"
          :class="page === menu.id ? 'active' : ''"
          class="sidebar-link w-full text-left">
          <span x-html="menu.icon"></span>
          <span x-text="menu.label"></span>
        </button>
      </template>
    </nav>
    <div class="p-3 border-t border-slate-700">
      <div class="flex items-center gap-3 px-4 py-2.5 mb-1">
        <div class="w-8 h-8 rounded-full bg-indigo-500 flex items-center justify-center text-white text-xs font-bold" x-text="(user?.name||'A').charAt(0)"></div>
        <div class="min-w-0">
          <p class="text-white text-sm font-medium truncate" x-text="user?.name||'Admin'"></p>
          <p class="text-slate-400 text-xs truncate" x-text="user?.email||''"></p>
        </div>
      </div>
      <button @click="doLogout()" class="sidebar-link w-full text-left text-red-400 hover:bg-red-500/10 hover:text-red-300">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
        Logout
      </button>
    </div>
  </aside>

  <!-- Main Content -->
  <main class="flex-1 overflow-y-auto">

    <!-- Top Bar -->
    <div class="bg-white border-b border-slate-200 px-6 py-4 flex items-center justify-between sticky top-0 z-10">
      <h2 class="font-semibold text-slate-700 text-base" x-text="menus.find(m=>m.id===page)?.label || 'Dashboard'"></h2>
      <div class="flex items-center gap-2">
        <span x-show="loading" class="text-xs text-slate-400 animate-pulse">Memuat…</span>
        <span x-show="toast.show" x-text="toast.msg"
          :class="toast.ok ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700'"
          class="text-xs font-medium px-3 py-1.5 rounded-full transition-all"></span>
      </div>
    </div>

    <div class="p-6">

      <!-- ── USERS ── -->
      <section x-show="page==='users'" x-cloak>
        <div class="flex items-center justify-between mb-4">
          <h3 class="font-semibold text-lg">Manajemen User</h3>
          <button @click="openModal('userForm', {name:'',email:'',password:'',role:'branch_admin', branch_id:''})"
            class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium px-4 py-2 rounded-xl">+ Tambah User</button>
        </div>
        <div class="bg-white rounded-2xl shadow-sm overflow-hidden">
          <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-500 text-xs uppercase tracking-wide">
              <tr>
                <th class="px-4 py-3 text-left">Nama</th>
                <th class="px-4 py-3 text-left">Email</th>
                <th class="px-4 py-3 text-left">Branch</th>
                <th class="px-4 py-3 text-left">Role</th>
                <th class="px-4 py-3 text-left">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <template x-for="u in list" :key="u.id">
                <tr class="hover:bg-slate-50">
                  <td class="px-4 py-3 font-medium" x-text="u.name"></td>
                  <td class="px-4 py-3 text-slate-500" x-text="u.email"></td>
                  <td class="px-4 py-3">
                    <template x-for="b in (u.branches||[])" :key="b.id">
                      <span class="inline-block bg-indigo-100 text-indigo-700 text-xs rounded-full px-2 py-0.5 mr-1" x-text="b.name"></span>
                    </template>
                  </td>
                  <td class="px-4 py-3">
                    <template x-for="b in (u.branches||[])" :key="b.id">
                      <span class="inline-block text-xs rounded-full px-2 py-0.5 mr-1"
                        :class="b.pivot?.role==='super_admin'?'bg-purple-100 text-purple-700': b.pivot?.role==='branch_admin'?'bg-blue-100 text-blue-700':'bg-green-100 text-green-700'"
                        x-text="b.pivot?.role||'-'"></span>
                    </template>
                  </td>
                  <td class="px-4 py-3 flex gap-2">
                    <button @click="openModal('userForm', {...u, password:'', branch_id: u.branches?.[0]?.id||'', role: u.branches?.[0]?.pivot?.role||'branch_admin'})"
                      class="text-indigo-600 hover:underline text-xs">Edit</button>
                    <button @click="deleteItem('users', u.id)"
                      class="text-red-500 hover:underline text-xs">Hapus</button>
                  </td>
                </tr>
              </template>
              <tr x-show="list.length===0"><td colspan="5" class="px-4 py-8 text-center text-slate-400">Belum ada data</td></tr>
            </tbody>
          </table>
        </div>
      </section>

      <!-- ── BRANCHES ── -->
      <section x-show="page==='branches'" x-cloak>
        <div class="flex items-center justify-between mb-4">
          <h3 class="font-semibold text-lg">Manajemen Branch</h3>
          <button @click="openModal('branchForm', {name:'',code:'',address:'',phone:'',is_active:true})"
            class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium px-4 py-2 rounded-xl">+ Tambah Branch</button>
        </div>
        <div class="bg-white rounded-2xl shadow-sm overflow-hidden">
          <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-500 text-xs uppercase tracking-wide">
              <tr>
                <th class="px-4 py-3 text-left">Nama</th>
                <th class="px-4 py-3 text-left">Kode</th>
                <th class="px-4 py-3 text-left">Telepon</th>
                <th class="px-4 py-3 text-left">Status</th>
                <th class="px-4 py-3 text-left">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <template x-for="b in list" :key="b.id">
                <tr class="hover:bg-slate-50">
                  <td class="px-4 py-3 font-medium" x-text="b.name"></td>
                  <td class="px-4 py-3 font-mono text-xs bg-slate-50 rounded" x-text="b.code"></td>
                  <td class="px-4 py-3 text-slate-500" x-text="b.phone||'-'"></td>
                  <td class="px-4 py-3">
                    <span :class="b.is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-500'"
                      class="text-xs font-medium px-2 py-0.5 rounded-full" x-text="b.is_active ? 'Aktif' : 'Non-Aktif'"></span>
                  </td>
                  <td class="px-4 py-3 flex gap-2">
                    <button @click="openModal('branchForm', {...b})" class="text-indigo-600 hover:underline text-xs">Edit</button>
                    <button @click="toggleBranch(b)" class="text-amber-500 hover:underline text-xs" x-text="b.is_active?'Nonaktifkan':'Aktifkan'"></button>
                    <button @click="deleteItem('branches', b.id)" class="text-red-500 hover:underline text-xs">Hapus</button>
                  </td>
                </tr>
              </template>
              <tr x-show="list.length===0"><td colspan="5" class="px-4 py-8 text-center text-slate-400">Belum ada data</td></tr>
            </tbody>
          </table>
        </div>
      </section>

      <!-- ── BILLING ── -->
      <section x-show="page==='billing'" x-cloak>
        <div class="flex items-center justify-between mb-4">
          <h3 class="font-semibold text-lg">Billing Branch</h3>
          <button @click="openModal('billingForm', {branch_id:'',plan:'monthly',amount:'',due_date:'',status:'unpaid',note:''})"
            class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium px-4 py-2 rounded-xl">+ Tambah Tagihan</button>
        </div>
        <div class="bg-white rounded-2xl shadow-sm overflow-hidden">
          <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-500 text-xs uppercase tracking-wide">
              <tr>
                <th class="px-4 py-3 text-left">Branch</th>
                <th class="px-4 py-3 text-left">Plan</th>
                <th class="px-4 py-3 text-left">Nominal</th>
                <th class="px-4 py-3 text-left">Jatuh Tempo</th>
                <th class="px-4 py-3 text-left">Status</th>
                <th class="px-4 py-3 text-left">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <template x-for="b in list" :key="b.id">
                <tr class="hover:bg-slate-50">
                  <td class="px-4 py-3 font-medium" x-text="b.branch?.name||b.branch_id"></td>
                  <td class="px-4 py-3 capitalize" x-text="b.plan"></td>
                  <td class="px-4 py-3 font-mono" x-text="'Rp '+Number(b.amount).toLocaleString('id-ID')"></td>
                  <td class="px-4 py-3 text-slate-500" x-text="b.due_date"></td>
                  <td class="px-4 py-3">
                    <span :class="{
                      'bg-emerald-100 text-emerald-700': b.status==='paid',
                      'bg-red-100 text-red-600': b.status==='overdue',
                      'bg-amber-100 text-amber-700': b.status==='unpaid'
                    }" class="text-xs font-medium px-2 py-0.5 rounded-full capitalize" x-text="b.status"></span>
                  </td>
                  <td class="px-4 py-3 flex gap-2">
                    <button @click="openModal('billingForm', {...b, branch_id: b.branch_id})" class="text-indigo-600 hover:underline text-xs">Edit</button>
                    <button @click="markBillingPaid(b)" x-show="b.status!=='paid'" class="text-emerald-600 hover:underline text-xs">Lunas</button>
                    <button @click="deleteItem('billings', b.id)" class="text-red-500 hover:underline text-xs">Hapus</button>
                  </td>
                </tr>
              </template>
              <tr x-show="list.length===0"><td colspan="6" class="px-4 py-8 text-center text-slate-400">Belum ada data</td></tr>
            </tbody>
          </table>
        </div>
      </section>

      <!-- ── NOTIFICATIONS ── -->
      <section x-show="page==='notifications'" x-cloak>
        <div class="flex items-center justify-between mb-4">
          <h3 class="font-semibold text-lg">Notifikasi & Pengumuman</h3>
          <button @click="openModal('notifForm', {title:'',body:'',type:'info',branch_ids:[]})"
            class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium px-4 py-2 rounded-xl">+ Kirim Notifikasi</button>
        </div>
        <div class="space-y-3">
          <template x-for="n in list" :key="n.id">
            <div class="bg-white rounded-2xl shadow-sm p-4 flex items-start gap-4">
              <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0"
                :class="{'bg-blue-100':n.type==='info','bg-amber-100':n.type==='warning','bg-red-100':n.type==='critical','bg-emerald-100':n.type==='update'}">
                <span class="text-lg" x-text="{'info':'ℹ️','warning':'⚠️','critical':'🚨','update':'🔄'}[n.type]||'📢'"></span>
              </div>
              <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2 mb-1">
                  <span class="font-semibold text-sm" x-text="n.title"></span>
                  <span class="text-xs rounded-full px-2 py-0.5"
                    :class="{'bg-blue-100 text-blue-700':n.type==='info','bg-amber-100 text-amber-700':n.type==='warning','bg-red-100 text-red-700':n.type==='critical','bg-emerald-100 text-emerald-700':n.type==='update'}"
                    x-text="n.type"></span>
                </div>
                <p class="text-sm text-slate-500 mb-2" x-text="n.body"></p>
                <div class="flex flex-wrap gap-1">
                  <template x-for="b in (n.branches||[])" :key="b.id">
                    <span class="bg-slate-100 text-slate-600 text-xs px-2 py-0.5 rounded-full" x-text="b.name"></span>
                  </template>
                </div>
              </div>
              <div class="flex gap-2 flex-shrink-0">
                <button @click="openModal('notifForm', {id:n.id, title:n.title, body:n.body, type:n.type, branch_ids:(n.branches||[]).map(b=>b.id)})" class="text-indigo-600 hover:underline text-xs">Edit</button>
                <button @click="deleteItem('notifications', n.id)" class="text-red-500 hover:underline text-xs">Hapus</button>
              </div>
            </div>
          </template>
          <div x-show="list.length===0" class="bg-white rounded-2xl p-8 text-center text-slate-400">Belum ada notifikasi</div>
        </div>
      </section>

      <!-- ── SMTP ── -->
      <section x-show="page==='smtp'" x-cloak>
        <div class="flex items-center justify-between mb-4">
          <h3 class="font-semibold text-lg">Pengaturan SMTP</h3>
          <button @click="openModal('smtpForm', {name:'',host:'',port:587,username:'',password:'',encryption:'tls',from_address:'',from_name:'',is_default:false})"
            class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium px-4 py-2 rounded-xl">+ Tambah SMTP</button>
        </div>
        <div class="space-y-3">
          <template x-for="s in list" :key="s.id">
            <div class="bg-white rounded-2xl shadow-sm p-4">
              <div class="flex items-center justify-between mb-3">
                <div class="flex items-center gap-3">
                  <span class="font-semibold" x-text="s.name"></span>
                  <span x-show="s.is_default" class="bg-indigo-100 text-indigo-700 text-xs px-2 py-0.5 rounded-full font-medium">Default</span>
                </div>
                <div class="flex gap-2">
                  <button @click="testSmtp(s)" class="text-xs bg-emerald-100 text-emerald-700 hover:bg-emerald-200 px-3 py-1 rounded-full">Test Kirim</button>
                  <button @click="openModal('smtpForm', {...s})" class="text-indigo-600 hover:underline text-xs">Edit</button>
                  <button @click="deleteItem('smtp', s.id)" class="text-red-500 hover:underline text-xs">Hapus</button>
                </div>
              </div>
              <div class="grid grid-cols-2 md:grid-cols-4 gap-3 text-sm">
                <div><span class="text-slate-400 text-xs">Host</span><p class="font-mono text-xs mt-0.5" x-text="s.host"></p></div>
                <div><span class="text-slate-400 text-xs">Port</span><p class="font-mono text-xs mt-0.5" x-text="s.port"></p></div>
                <div><span class="text-slate-400 text-xs">Enkripsi</span><p class="text-xs mt-0.5 capitalize" x-text="s.encryption"></p></div>
                <div><span class="text-slate-400 text-xs">From</span><p class="text-xs mt-0.5 truncate" x-text="s.from_address"></p></div>
              </div>
            </div>
          </template>
          <div x-show="list.length===0" class="bg-white rounded-2xl p-8 text-center text-slate-400">Belum ada konfigurasi SMTP</div>
        </div>

        <!-- Report Schedules -->
        <div class="flex items-center justify-between mt-8 mb-4">
          <h3 class="font-semibold text-lg">Jadwal Laporan Harian per Branch</h3>
        </div>
        <div class="bg-white rounded-2xl shadow-sm overflow-hidden">
          <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-500 text-xs uppercase tracking-wide">
              <tr>
                <th class="px-4 py-3 text-left">Branch</th>
                <th class="px-4 py-3 text-left">Email Tujuan</th>
                <th class="px-4 py-3 text-left">Jam Kirim</th>
                <th class="px-4 py-3 text-left">Status</th>
                <th class="px-4 py-3 text-left">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <template x-for="r in schedules" :key="r.id||r.branch_id">
                <tr class="hover:bg-slate-50">
                  <td class="px-4 py-3 font-medium" x-text="r.branch?.name||r.branch_id"></td>
                  <td class="px-4 py-3 text-slate-500" x-text="r.recipient_email"></td>
                  <td class="px-4 py-3 font-mono" x-text="r.send_at||'-'"></td>
                  <td class="px-4 py-3">
                    <span :class="r.is_active?'bg-emerald-100 text-emerald-700':'bg-slate-100 text-slate-500'"
                      class="text-xs font-medium px-2 py-0.5 rounded-full" x-text="r.is_active?'Aktif':'Nonaktif'"></span>
                  </td>
                  <td class="px-4 py-3">
                    <button @click="openModal('scheduleForm', {...r, branch_id: r.branch_id})" class="text-indigo-600 hover:underline text-xs">Edit</button>
                  </td>
                </tr>
              </template>
              <tr x-show="schedules.length===0"><td colspan="5" class="px-4 py-8 text-center text-slate-400">Belum ada jadwal</td></tr>
            </tbody>
          </table>
        </div>
        <div class="mt-3 flex justify-end">
          <button @click="openModal('scheduleForm', {branch_id:'',recipient_email:'',send_at:'07:00',is_active:true})"
            class="bg-slate-700 hover:bg-slate-600 text-white text-sm font-medium px-4 py-2 rounded-xl">+ Tambah Jadwal</button>
        </div>
      </section>

    </div><!-- /p-6 -->
  </main>
</div>

<!-- ═══ MODALS ═══ -->

<!-- User Form Modal -->
<div x-show="modal==='userForm'" x-cloak @click.self="modal=null"
  class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-4">
  <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg p-6" @click.stop>
    <h3 class="font-bold text-lg mb-5" x-text="form.id ? 'Edit User' : 'Tambah User'"></h3>
    <div class="space-y-3">
      <div class="grid grid-cols-2 gap-3">
        <div>
          <label class="text-xs font-medium text-slate-600 mb-1 block">Nama *</label>
          <input x-model="form.name" class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400" placeholder="Nama lengkap">
        </div>
        <div>
          <label class="text-xs font-medium text-slate-600 mb-1 block">Email *</label>
          <input x-model="form.email" type="email" class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400" placeholder="email@domain.com">
        </div>
      </div>
      <div>
        <label class="text-xs font-medium text-slate-600 mb-1 block">Password <span x-text="form.id ? '(kosongkan jika tidak diubah)' : '*'"></span></label>
        <input x-model="form.password" type="password" class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400" placeholder="••••••••">
      </div>
      <div class="grid grid-cols-2 gap-3">
        <div>
          <label class="text-xs font-medium text-slate-600 mb-1 block">Branch</label>
          <select x-model="form.branch_id" class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
            <option value="">-- Pilih Branch --</option>
            <template x-for="b in branches" :key="b.id">
              <option :value="b.id" x-text="b.name"></option>
            </template>
          </select>
        </div>
        <div>
          <label class="text-xs font-medium text-slate-600 mb-1 block">Role</label>
          <select x-model="form.role" class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
            <option value="super_admin">super_admin</option>
            <option value="branch_admin">branch_admin</option>
            <option value="cashier">cashier</option>
          </select>
        </div>
      </div>
    </div>
    <div class="flex gap-3 mt-6">
      <button @click="modal=null" class="flex-1 border border-slate-200 rounded-xl py-2 text-sm hover:bg-slate-50">Batal</button>
      <button @click="saveUser()" class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl py-2 text-sm font-medium">Simpan</button>
    </div>
  </div>
</div>

<!-- Branch Form Modal -->
<div x-show="modal==='branchForm'" x-cloak @click.self="modal=null"
  class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-4">
  <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg p-6" @click.stop>
    <h3 class="font-bold text-lg mb-5" x-text="form.id ? 'Edit Branch' : 'Tambah Branch'"></h3>
    <div class="space-y-3">
      <div class="grid grid-cols-2 gap-3">
        <div>
          <label class="text-xs font-medium text-slate-600 mb-1 block">Nama *</label>
          <input x-model="form.name" class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
        </div>
        <div>
          <label class="text-xs font-medium text-slate-600 mb-1 block">Kode * (unik)</label>
          <input x-model="form.code" class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-indigo-400" placeholder="PST">
        </div>
      </div>
      <div>
        <label class="text-xs font-medium text-slate-600 mb-1 block">Alamat</label>
        <textarea x-model="form.address" rows="2" class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400"></textarea>
      </div>
      <div>
        <label class="text-xs font-medium text-slate-600 mb-1 block">No. Telepon</label>
        <input x-model="form.phone" class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
      </div>
      <label class="flex items-center gap-2 cursor-pointer">
        <input type="checkbox" x-model="form.is_active" class="rounded">
        <span class="text-sm text-slate-600">Branch Aktif</span>
      </label>
    </div>
    <div class="flex gap-3 mt-6">
      <button @click="modal=null" class="flex-1 border border-slate-200 rounded-xl py-2 text-sm hover:bg-slate-50">Batal</button>
      <button @click="saveBranch()" class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl py-2 text-sm font-medium">Simpan</button>
    </div>
  </div>
</div>

<!-- Billing Form Modal -->
<div x-show="modal==='billingForm'" x-cloak @click.self="modal=null"
  class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-4">
  <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg p-6" @click.stop>
    <h3 class="font-bold text-lg mb-5" x-text="form.id ? 'Edit Tagihan' : 'Tambah Tagihan'"></h3>
    <div class="space-y-3">
      <div>
        <label class="text-xs font-medium text-slate-600 mb-1 block">Branch *</label>
        <select x-model="form.branch_id" class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
          <option value="">-- Pilih Branch --</option>
          <template x-for="b in branches" :key="b.id">
            <option :value="b.id" x-text="b.name"></option>
          </template>
        </select>
      </div>
      <div class="grid grid-cols-2 gap-3">
        <div>
          <label class="text-xs font-medium text-slate-600 mb-1 block">Plan</label>
          <select x-model="form.plan" class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
            <option value="monthly">Monthly</option>
            <option value="quarterly">Quarterly</option>
            <option value="yearly">Yearly</option>
            <option value="lifetime">Lifetime</option>
          </select>
        </div>
        <div>
          <label class="text-xs font-medium text-slate-600 mb-1 block">Nominal (Rp) *</label>
          <input x-model="form.amount" type="number" class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
        </div>
      </div>
      <div class="grid grid-cols-2 gap-3">
        <div>
          <label class="text-xs font-medium text-slate-600 mb-1 block">Jatuh Tempo</label>
          <input x-model="form.due_date" type="date" class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
        </div>
        <div>
          <label class="text-xs font-medium text-slate-600 mb-1 block">Status</label>
          <select x-model="form.status" class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
            <option value="unpaid">Unpaid</option>
            <option value="paid">Paid</option>
            <option value="overdue">Overdue</option>
          </select>
        </div>
      </div>
      <div>
        <label class="text-xs font-medium text-slate-600 mb-1 block">Catatan</label>
        <textarea x-model="form.note" rows="2" class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400"></textarea>
      </div>
    </div>
    <div class="flex gap-3 mt-6">
      <button @click="modal=null" class="flex-1 border border-slate-200 rounded-xl py-2 text-sm hover:bg-slate-50">Batal</button>
      <button @click="saveBilling()" class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl py-2 text-sm font-medium">Simpan</button>
    </div>
  </div>
</div>

<!-- Notification Form Modal -->
<div x-show="modal==='notifForm'" x-cloak @click.self="modal=null"
  class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-4">
  <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg p-6" @click.stop>
    <h3 class="font-bold text-lg mb-5" x-text="form.id ? 'Edit Notifikasi' : 'Kirim Notifikasi'"></h3>
    <div class="space-y-3">
      <div>
        <label class="text-xs font-medium text-slate-600 mb-1 block">Judul *</label>
        <input x-model="form.title" class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
      </div>
      <div>
        <label class="text-xs font-medium text-slate-600 mb-1 block">Isi Pesan *</label>
        <textarea x-model="form.body" rows="3" class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400"></textarea>
      </div>
      <div>
        <label class="text-xs font-medium text-slate-600 mb-1 block">Tipe</label>
        <select x-model="form.type" class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
          <option value="info">Info</option>
          <option value="warning">Warning</option>
          <option value="critical">Critical</option>
          <option value="update">Update</option>
        </select>
      </div>
      <div>
        <label class="text-xs font-medium text-slate-600 mb-1 block">Kirim ke Branch (pilih banyak)</label>
        <div class="border border-slate-200 rounded-xl p-3 space-y-1 max-h-36 overflow-y-auto">
          <template x-for="b in branches" :key="b.id">
            <label class="flex items-center gap-2 cursor-pointer">
              <input type="checkbox" :value="b.id"
                :checked="form.branch_ids && form.branch_ids.includes(b.id)"
                @change="toggleBranchId(b.id)"
                class="rounded">
              <span class="text-sm" x-text="b.name"></span>
            </label>
          </template>
        </div>
      </div>
    </div>
    <div class="flex gap-3 mt-6">
      <button @click="modal=null" class="flex-1 border border-slate-200 rounded-xl py-2 text-sm hover:bg-slate-50">Batal</button>
      <button @click="saveNotif()" class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl py-2 text-sm font-medium">Kirim / Simpan</button>
    </div>
  </div>
</div>

<!-- SMTP Form Modal -->
<div x-show="modal==='smtpForm'" x-cloak @click.self="modal=null"
  class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-4">
  <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg p-6" @click.stop>
    <h3 class="font-bold text-lg mb-5" x-text="form.id ? 'Edit SMTP' : 'Tambah SMTP'"></h3>
    <div class="space-y-3">
      <div>
        <label class="text-xs font-medium text-slate-600 mb-1 block">Nama Konfigurasi *</label>
        <input x-model="form.name" class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400" placeholder="Gmail Produksi">
      </div>
      <div class="grid grid-cols-3 gap-3">
        <div class="col-span-2">
          <label class="text-xs font-medium text-slate-600 mb-1 block">Host *</label>
          <input x-model="form.host" class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-indigo-400" placeholder="smtp.gmail.com">
        </div>
        <div>
          <label class="text-xs font-medium text-slate-600 mb-1 block">Port</label>
          <input x-model="form.port" type="number" class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-indigo-400">
        </div>
      </div>
      <div class="grid grid-cols-2 gap-3">
        <div>
          <label class="text-xs font-medium text-slate-600 mb-1 block">Username</label>
          <input x-model="form.username" class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
        </div>
        <div>
          <label class="text-xs font-medium text-slate-600 mb-1 block">Password</label>
          <input x-model="form.password" type="password" class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
        </div>
      </div>
      <div class="grid grid-cols-2 gap-3">
        <div>
          <label class="text-xs font-medium text-slate-600 mb-1 block">Enkripsi</label>
          <select x-model="form.encryption" class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
            <option value="tls">TLS</option>
            <option value="ssl">SSL</option>
            <option value="none">None</option>
          </select>
        </div>
        <div>
          <label class="text-xs font-medium text-slate-600 mb-1 block">From Name</label>
          <input x-model="form.from_name" class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
        </div>
      </div>
      <div>
        <label class="text-xs font-medium text-slate-600 mb-1 block">From Address</label>
        <input x-model="form.from_address" type="email" class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
      </div>
      <label class="flex items-center gap-2 cursor-pointer">
        <input type="checkbox" x-model="form.is_default" class="rounded">
        <span class="text-sm text-slate-600">Set sebagai SMTP default</span>
      </label>
    </div>
    <div class="flex gap-3 mt-6">
      <button @click="modal=null" class="flex-1 border border-slate-200 rounded-xl py-2 text-sm hover:bg-slate-50">Batal</button>
      <button @click="saveSmtp()" class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl py-2 text-sm font-medium">Simpan</button>
    </div>
  </div>
</div>

<!-- Schedule Form Modal -->
<div x-show="modal==='scheduleForm'" x-cloak @click.self="modal=null"
  class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-4">
  <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-6" @click.stop>
    <h3 class="font-bold text-lg mb-5">Jadwal Laporan Harian</h3>
    <div class="space-y-3">
      <div>
        <label class="text-xs font-medium text-slate-600 mb-1 block">Branch *</label>
        <select x-model="form.branch_id" class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
          <option value="">-- Pilih Branch --</option>
          <template x-for="b in branches" :key="b.id">
            <option :value="b.id" x-text="b.name"></option>
          </template>
        </select>
      </div>
      <div>
        <label class="text-xs font-medium text-slate-600 mb-1 block">Email Tujuan *</label>
        <input x-model="form.recipient_email" type="email" class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
      </div>
      <div>
        <label class="text-xs font-medium text-slate-600 mb-1 block">Jam Kirim</label>
        <input x-model="form.send_at" type="time" class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
      </div>
      <label class="flex items-center gap-2 cursor-pointer">
        <input type="checkbox" x-model="form.is_active" class="rounded">
        <span class="text-sm text-slate-600">Aktifkan jadwal</span>
      </label>
    </div>
    <div class="flex gap-3 mt-6">
      <button @click="modal=null" class="flex-1 border border-slate-200 rounded-xl py-2 text-sm hover:bg-slate-50">Batal</button>
      <button @click="saveSchedule()" class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl py-2 text-sm font-medium">Simpan</button>
    </div>
  </div>
</div>

<!-- ═══ ALPINE.JS APP ═══ -->
<script>
function adminApp(apiBase) {
  return {
    apiBase,
    token: localStorage.getItem('kinaya_admin_token') || '',
    user: JSON.parse(localStorage.getItem('kinaya_admin_user') || 'null'),
    page: 'users',
    modal: null,
    form: {},
    list: [],
    branches: [],
    schedules: [],
    loading: false,
    loginLoading: false,
    loginError: '',
    loginForm: { email: 'admin@kinaya.test', password: '' },
    toast: { show: false, msg: '', ok: true },

    menus: [
      { id: 'users',         label: 'Users',        icon: '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>' },
      { id: 'branches',      label: 'Branch',       icon: '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-2 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>' },
      { id: 'billing',       label: 'Billing',      icon: '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>' },
      { id: 'notifications', label: 'Notifikasi',   icon: '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>' },
      { id: 'smtp',          label: 'SMTP & Jadwal', icon: '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>' },
    ],

    init() {
      if (this.token) { this.loadPage(); this.loadBranches(); }
    },

    showToast(msg, ok = true) {
      this.toast = { show: true, msg, ok };
      setTimeout(() => this.toast.show = false, 3000);
    },

    async req(method, path, body = null) {
      const headers = { 'Accept': 'application/json', 'Content-Type': 'application/json' };
      if (this.token) headers['Authorization'] = 'Bearer ' + this.token;
      const res = await fetch(this.apiBase + path, {
        method, headers,
        body: body ? JSON.stringify(body) : null,
      });
      const data = await res.json().catch(() => ({}));
      if (!res.ok) throw data;
      return data;
    },

    // ── Auth ──
    async doLogin() {
      this.loginLoading = true; this.loginError = '';
      try {
        const data = await this.req('POST', '/login', this.loginForm);
        // Check if super_admin
        const role = data.branches?.[0]?.pivot?.role;
        if (role !== 'super_admin') {
          this.loginError = 'Akses ditolak. Hanya super_admin yang dapat masuk ke panel ini.';
          this.loginLoading = false; return;
        }
        this.token = data.token;
        this.user = data.user;
        localStorage.setItem('kinaya_admin_token', data.token);
        localStorage.setItem('kinaya_admin_user', JSON.stringify(data.user));
        await this.loadBranches();
        this.loadPage();
      } catch (e) {
        this.loginError = e?.message || e?.email?.[0] || 'Login gagal';
      }
      this.loginLoading = false;
    },

    doLogout() {
      this.token = ''; this.user = null;
      localStorage.removeItem('kinaya_admin_token');
      localStorage.removeItem('kinaya_admin_user');
      this.req('POST', '/logout').catch(() => {});
    },

    // ── Data loaders ──
    async loadBranches() {
      try {
        const data = await this.req('GET', '/admin/branches');
        this.branches = data.data || data;
      } catch (_) {}
    },

    async loadPage() {
      this.loading = true; this.list = [];
      try {
        const map = { users: '/admin/users', branches: '/admin/branches', billing: '/admin/billings', notifications: '/admin/notifications', smtp: '/admin/smtp' };
        const url = map[this.page];
        if (!url) { this.loading = false; return; }
        const data = await this.req('GET', url);
        this.list = data.data || data;
        if (this.page === 'smtp') {
          const sch = await this.req('GET', '/admin/report-schedules');
          this.schedules = sch.data || sch;
        }
      } catch (e) {
        if (e?.message?.includes('403') || e?.message?.includes('Unauthorized')) {
          this.showToast('Sesi habis, silakan login ulang', false);
          this.doLogout();
        }
      }
      this.loading = false;
    },

    openModal(name, data) {
      this.form = { ...data };
      this.modal = name;
    },

    // ── Users ──
    async saveUser() {
      try {
        const body = { name: this.form.name, email: this.form.email };
        if (this.form.password) body.password = this.form.password;
        if (this.form.branch_id) {
          body.branch_id = this.form.branch_id;
          body.role = this.form.role || 'branch_admin';
        }
        if (this.form.id) {
          await this.req('PUT', '/admin/users/' + this.form.id, body);
        } else {
          await this.req('POST', '/admin/users', body);
        }
        this.modal = null; this.loadPage();
        this.showToast(this.form.id ? 'User diperbarui' : 'User ditambahkan');
      } catch (e) {
        this.showToast(JSON.stringify(e?.errors || e?.message || 'Gagal'), false);
      }
    },

    async deleteItem(resource, id) {
      if (!confirm('Hapus data ini?')) return;
      try {
        await this.req('DELETE', '/admin/' + resource + '/' + id);
        this.loadPage();
        this.showToast('Data dihapus');
      } catch (e) {
        this.showToast(e?.message || 'Gagal hapus', false);
      }
    },

    // ── Branches ──
    async saveBranch() {
      try {
        const body = { name: this.form.name, code: this.form.code, address: this.form.address, phone: this.form.phone, is_active: this.form.is_active };
        if (this.form.id) {
          await this.req('PUT', '/admin/branches/' + this.form.id, body);
        } else {
          await this.req('POST', '/admin/branches', body);
        }
        this.modal = null; this.loadPage(); this.loadBranches();
        this.showToast('Branch disimpan');
      } catch (e) {
        this.showToast(JSON.stringify(e?.errors || e?.message || 'Gagal'), false);
      }
    },

    async toggleBranch(b) {
      try {
        await this.req('PATCH', '/admin/branches/' + b.id + '/toggle');
        this.loadPage();
        this.showToast('Status branch diperbarui');
      } catch (e) {
        this.showToast('Gagal', false);
      }
    },

    // ── Billing ──
    async saveBilling() {
      try {
        const body = { branch_id: this.form.branch_id, plan: this.form.plan, amount: this.form.amount, due_date: this.form.due_date, status: this.form.status, note: this.form.note };
        if (this.form.id) {
          await this.req('PUT', '/admin/billings/' + this.form.id, body);
        } else {
          await this.req('POST', '/admin/billings', body);
        }
        this.modal = null; this.loadPage();
        this.showToast('Tagihan disimpan');
      } catch (e) {
        this.showToast(JSON.stringify(e?.errors || e?.message || 'Gagal'), false);
      }
    },

    async markBillingPaid(b) {
      try {
        await this.req('PATCH', '/admin/billings/' + b.id + '/pay');
        this.loadPage();
        this.showToast('Tagihan ditandai lunas ✅');
      } catch (e) {
        this.showToast('Gagal', false);
      }
    },

    // ── Notifications ──
    toggleBranchId(id) {
      if (!this.form.branch_ids) this.form.branch_ids = [];
      const idx = this.form.branch_ids.indexOf(id);
      if (idx === -1) this.form.branch_ids.push(id);
      else this.form.branch_ids.splice(idx, 1);
    },

    async saveNotif() {
      try {
        const body = { title: this.form.title, body: this.form.body, type: this.form.type, branch_ids: this.form.branch_ids || [] };
        if (this.form.id) {
          await this.req('PUT', '/admin/notifications/' + this.form.id, body);
        } else {
          await this.req('POST', '/admin/notifications', body);
        }
        this.modal = null; this.loadPage();
        this.showToast('Notifikasi dikirim');
      } catch (e) {
        this.showToast(JSON.stringify(e?.errors || e?.message || 'Gagal'), false);
      }
    },

    // ── SMTP ──
    async saveSmtp() {
      try {
        const body = { name: this.form.name, host: this.form.host, port: this.form.port, username: this.form.username, password: this.form.password, encryption: this.form.encryption, from_address: this.form.from_address, from_name: this.form.from_name, is_default: this.form.is_default };
        if (this.form.id) {
          await this.req('PUT', '/admin/smtp/' + this.form.id, body);
        } else {
          await this.req('POST', '/admin/smtp', body);
        }
        this.modal = null; this.loadPage();
        this.showToast('SMTP disimpan');
      } catch (e) {
        this.showToast(JSON.stringify(e?.errors || e?.message || 'Gagal'), false);
      }
    },

    async testSmtp(s) {
      try {
        const email = prompt('Kirim email test ke:');
        if (!email) return;
        await this.req('POST', '/admin/smtp/' + s.id + '/test', { to: email });
        this.showToast('Email test terkirim ke ' + email + ' ✅');
      } catch (e) {
        this.showToast(e?.message || 'Gagal kirim test', false);
      }
    },

    // ── Report Schedules ──
    async saveSchedule() {
      try {
        const body = { recipient_email: this.form.recipient_email, send_at: this.form.send_at, is_active: this.form.is_active };
        await this.req('PUT', '/admin/report-schedules/' + this.form.branch_id, body);
        this.modal = null; this.loadPage();
        this.showToast('Jadwal disimpan');
      } catch (e) {
        this.showToast(JSON.stringify(e?.errors || e?.message || 'Gagal'), false);
      }
    },
  }
}
</script>
</body>
</html>
