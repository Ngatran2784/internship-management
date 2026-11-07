<style>
/* ── Shared design system: ms-* (users & periods pages) ── */
.ms-page { --ms-pri: #4f46e5; --ms-pri2: #7c3aed; --ms-ink: #0f172a; --ms-muted: #64748b; --ms-line: #e8ecf4; --ms-soft: #f6f7fb; }
.ms-page, .ms-page * { box-sizing: border-box; }
.ms-page { color: var(--ms-ink); width: 100%; max-width: 1320px; margin: 0 auto; padding: 1.5rem 1rem 3rem; }
.ms-page a { text-decoration: none; }

/* Alerts */
.ms-alert { position: relative; display: flex; align-items: flex-start; gap: .75rem; padding: .95rem 3rem .95rem 1.1rem; margin-bottom: 1.25rem; border-radius: 14px; border-left: 4px solid; font-weight: 500; box-shadow: 0 8px 24px -12px rgba(15,23,42,.2); }
.ms-alert.success { background: #ecfdf5; border-color: #10b981; color: #065f46; }
.ms-alert.danger  { background: #fef2f2; border-color: #ef4444; color: #991b1b; }
.ms-alert-close { position: absolute; right: .8rem; top: .9rem; width: 1.8rem; height: 1.8rem; border: 0; border-radius: 8px; background: transparent; color: inherit; cursor: pointer; font-size: 1.2rem; line-height: 1; opacity: .6; }
.ms-alert-close:hover { opacity: 1; background: rgba(0,0,0,.06); }
.ms-alert-close::before { content: "×"; }

/* Hero */
.ms-hero { position: relative; overflow: hidden; display: flex; flex-wrap: wrap; gap: 1rem; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; padding: 2rem 2.25rem; border-radius: 22px; color: #fff; background: linear-gradient(120deg, #4f46e5 0%, #7c3aed 60%, #c026d3 125%); box-shadow: 0 20px 40px -18px rgba(79,70,229,.6); }
.ms-hero::before, .ms-hero::after { content: ""; position: absolute; border-radius: 50%; pointer-events: none; background: rgba(255,255,255,.1); }
.ms-hero::before { width: 280px; height: 280px; right: -80px; top: -130px; }
.ms-hero::after  { width: 170px; height: 170px; right: 180px; bottom: -120px; background: rgba(255,255,255,.07); }
.ms-hero > * { position: relative; z-index: 1; }
.ms-eyebrow { font-size: .74rem; font-weight: 600; letter-spacing: .09em; text-transform: uppercase; color: rgba(255,255,255,.8); margin-bottom: .3rem; }
.ms-hero h1 { margin: 0; font-size: 1.85rem; font-weight: 800; letter-spacing: -.02em; color: #fff; }
.ms-hero-sub { font-size: .88rem; color: rgba(255,255,255,.75); margin-top: .35rem; }
.ms-hero-stat { display: flex; gap: 1.5rem; flex-wrap: wrap; }
.ms-hero-stat-item { text-align: center; }
.ms-hero-stat-num { font-size: 1.5rem; font-weight: 800; color: #fff; line-height: 1.1; }
.ms-hero-stat-label { font-size: .7rem; color: rgba(255,255,255,.7); text-transform: uppercase; letter-spacing: .07em; margin-top: .15rem; }

/* CTA Button */
.ms-cta { display: inline-flex; align-items: center; gap: .5rem; padding: .75rem 1.4rem; border-radius: 12px; background: #fff; color: #4f46e5 !important; font-weight: 700; font-size: .9rem; box-shadow: 0 8px 18px -8px rgba(0,0,0,.4); transition: transform .15s, box-shadow .15s; border: none; cursor: pointer; white-space: nowrap; }
.ms-cta:hover { transform: translateY(-2px); box-shadow: 0 14px 24px -10px rgba(0,0,0,.45); }
.ms-cta-outline { background: transparent; color: #fff !important; border: 1.5px solid rgba(255,255,255,.5) !important; box-shadow: none; }
.ms-cta-outline:hover { background: rgba(255,255,255,.15); transform: translateY(-1px); }

/* Card */
.ms-card { background: #fff; border: 1px solid var(--ms-line); border-radius: 18px; box-shadow: 0 1px 2px rgba(15,23,42,.04), 0 10px 28px -12px rgba(15,23,42,.12); margin-bottom: 1.5rem; }

/* Filter bar */
.ms-filter { padding: 1.25rem 1.5rem; }
.ms-filter form { display: grid; grid-template-columns: 1fr 1.6fr auto; gap: 1rem; align-items: end; }
.ms-filter.cols-4 form { grid-template-columns: 1fr 1fr 1.4fr auto; }
.ms-field label { display: block; margin-bottom: .4rem; font-size: .72rem; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; color: var(--ms-muted); }
.ms-field select, .ms-field input { width: 100%; height: 44px; padding: 0 .95rem; border: 1px solid var(--ms-line); border-radius: 10px; background: var(--ms-soft); color: var(--ms-ink); font: inherit; font-size: .92rem; outline: none; transition: border-color .15s, box-shadow .15s, background .15s; }
.ms-field select { appearance: none; cursor: pointer; padding-right: 2.2rem; background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' fill='none' stroke='%2364748b' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M1 1.5l5 5 5-5'/%3E%3C/svg%3E"); background-repeat: no-repeat; background-position: right .9rem center; }
.ms-field select:focus, .ms-field input:focus { background: #fff; border-color: var(--ms-pri); box-shadow: 0 0 0 3px rgba(79,70,229,.13); }
.ms-btn-filter { height: 44px; padding: 0 1.5rem; border: 0; border-radius: 10px; cursor: pointer; display: inline-flex; align-items: center; gap: .5rem; background: linear-gradient(120deg, #4f46e5, #7c3aed); color: #fff; font: inherit; font-size: .9rem; font-weight: 700; box-shadow: 0 8px 16px -8px rgba(79,70,229,.7); transition: transform .15s, box-shadow .15s; white-space: nowrap; }
.ms-btn-filter:hover { transform: translateY(-1px); box-shadow: 0 12px 20px -8px rgba(79,70,229,.8); }

/* Table */
.ms-table-wrap { overflow-x: auto; }
.ms-table { width: 100%; border-collapse: collapse; min-width: 820px; }
.ms-table th { padding: .9rem 1.2rem; text-align: left; white-space: nowrap; background: var(--ms-soft); color: var(--ms-muted); font-size: .72rem; font-weight: 700; letter-spacing: .07em; text-transform: uppercase; border-bottom: 1px solid var(--ms-line); }
.ms-table th.r, .ms-table td.r { text-align: right; }
.ms-table td { padding: .9rem 1.2rem; border-bottom: 1px solid #f0f2f8; vertical-align: middle; }
.ms-table tbody tr { transition: background .12s; }
.ms-table tbody tr:hover { background: #f8f9ff; }
.ms-table tbody tr:last-child td { border-bottom: none; }

/* User cell */
.ms-user { display: flex; align-items: center; gap: .85rem; }
.ms-avatar { flex: 0 0 auto; width: 2.6rem; height: 2.6rem; border-radius: 12px; display: grid; place-items: center; color: #fff; font-weight: 700; font-size: .95rem; box-shadow: 0 6px 12px -6px rgba(0,0,0,.4); }
.av0 { background: linear-gradient(135deg, #6366f1, #8b5cf6); }
.av1 { background: linear-gradient(135deg, #0ea5e9, #6366f1); }
.av2 { background: linear-gradient(135deg, #10b981, #0ea5e9); }
.av3 { background: linear-gradient(135deg, #f59e0b, #ef4444); }
.av4 { background: linear-gradient(135deg, #ec4899, #8b5cf6); }
.ms-name { font-weight: 700; color: var(--ms-ink); line-height: 1.3; }
.ms-sub  { font-size: .78rem; color: var(--ms-muted); margin-top: .1rem; }
.ms-id { display: inline-flex; align-items: center; justify-content: center; min-width: 2rem; height: 2rem; padding: 0 .5rem; border-radius: 8px; background: var(--ms-soft); color: var(--ms-muted); font-size: .8rem; font-weight: 700; }

/* Period icon cell */
.ms-period-icon { width: 2.8rem; height: 2.8rem; border-radius: 12px; display: grid; place-items: center; font-size: 1.2rem; flex-shrink: 0; }
.pi-internship { background: #eff6ff; color: #1d4ed8; }
.pi-thesis     { background: #f5f3ff; color: #7c3aed; }

/* Badges */
.ms-badge { display: inline-flex; align-items: center; gap: .35rem; padding: .3rem .75rem; border-radius: 999px; font-size: .76rem; font-weight: 600; }
.ms-badge.role   { background: #eef2ff; color: #4338ca; border: 1px solid #e0e7ff; margin: 0 .25rem .25rem 0; }
.ms-badge.on     { background: #ecfdf5; color: #047857; }
.ms-badge.off    { background: #fef2f2; color: #b91c1c; }
.ms-badge.on::before, .ms-badge.off::before { content: ""; width: .45rem; height: .45rem; border-radius: 50%; background: currentColor; flex-shrink: 0; }
.ms-badge.on::before  { box-shadow: 0 0 0 3px rgba(16,185,129,.2); }
.ms-badge.off::before { box-shadow: 0 0 0 3px rgba(239,68,68,.18); }
.ms-badge.draft  { background: #fefce8; color: #854d0e; border: 1px solid #fef08a; }
.ms-badge.active-p { background: #ecfdf5; color: #047857; border: 1px solid #a7f3d0; }
.ms-badge.closed { background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; }
.ms-badge.type-int  { background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; }
.ms-badge.type-thesis { background: #f5f3ff; color: #6d28d9; border: 1px solid #ddd6fe; }

/* Action buttons */
.ms-actions { display: inline-flex; align-items: center; gap: .4rem; }
.ms-actions form { margin: 0; }
.ms-btn { display: inline-flex; align-items: center; gap: .3rem; padding: .4rem .85rem; border-radius: 9px; cursor: pointer; background: #fff; border: 1px solid; font: inherit; font-size: .83rem; font-weight: 600; white-space: nowrap; transition: all .15s; text-decoration: none; }
.ms-btn.edit   { color: #4f46e5; border-color: #c7d2fe; }
.ms-btn.edit:hover   { background: #4f46e5; border-color: #4f46e5; color: #fff; transform: translateY(-1px); }
.ms-btn.view   { color: #0369a1; border-color: #bae6fd; }
.ms-btn.view:hover   { background: #0369a1; border-color: #0369a1; color: #fff; transform: translateY(-1px); }
.ms-btn.lock   { color: #b45309; border-color: #fde68a; }
.ms-btn.lock:hover   { background: #f59e0b; border-color: #f59e0b; color: #fff; transform: translateY(-1px); }
.ms-btn.unlock { color: #047857; border-color: #a7f3d0; }
.ms-btn.unlock:hover { background: #10b981; border-color: #10b981; color: #fff; transform: translateY(-1px); }
.ms-btn.del    { color: #b91c1c; border-color: #fecaca; }
.ms-btn.del:hover    { background: #ef4444; border-color: #ef4444; color: #fff; transform: translateY(-1px); }

/* Empty state */
.ms-empty { text-align: center; padding: 3.5rem 1rem; color: var(--ms-muted); }
.ms-empty-icon { font-size: 2.5rem; display: block; margin-bottom: .75rem; opacity: .45; }

/* Detail info grid */
.ms-detail-row { display: flex; align-items: flex-start; gap: .75rem; padding: .85rem 0; border-bottom: 1px solid var(--ms-line); }
.ms-detail-row:last-child { border-bottom: none; }
.ms-detail-icon { width: 2.1rem; height: 2.1rem; border-radius: .6rem; display: grid; place-items: center; font-size: .95rem; flex-shrink: 0; }
.ms-detail-label { font-size: .72rem; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; color: var(--ms-muted); margin-bottom: .2rem; }
.ms-detail-value { font-weight: 500; word-break: break-word; }

/* Timeline bar for dates */
.ms-timeline { display: flex; align-items: center; gap: .5rem; flex-wrap: wrap; }
.ms-timeline-date { font-weight: 600; font-size: .9rem; }
.ms-timeline-sep { color: var(--ms-muted); font-size: .75rem; }

@media (max-width: 767px) {
    .ms-hero { padding: 1.5rem 1.25rem; }
    .ms-filter form, .ms-filter.cols-4 form { grid-template-columns: 1fr; }
    .ms-btn-filter { width: 100%; }
}
</style>
