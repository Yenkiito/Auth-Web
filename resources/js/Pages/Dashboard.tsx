import AppShell from '@/Components/AppShell';
import { Head } from '@inertiajs/react';
import { Activity, Cpu, FolderKanban, KeyRound, ShieldCheck, Users, UserRound } from 'lucide-react';
type Props={stats:Record<string,number>};
export default function Dashboard({stats}:Props){const cards=[['Proyectos','projects',FolderKanban],['Socios','partners',Users],['Clientes','clients',UserRound],['Licencias','licenses',KeyRound],['Activas','active',ShieldCheck],['Expiradas','expired',Activity],['Dispositivos','devices',Cpu]] as const;return <AppShell title="Dashboard"><Head title="Dashboard"/><div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">{cards.map(([label,key,Icon])=><div className="panel p-5" key={key}><div className="mb-5 flex items-center justify-between text-slate-400"><span className="text-sm">{label}</span><Icon size={18}/></div><strong className="text-3xl">{stats[key]??0}</strong></div>)}</div></AppShell>}
