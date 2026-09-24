import AppShell from '@/Components/AppShell';
import DataTable from '@/Components/DataTable';
import { Field, Select } from '@/Components/FormField';
import Modal from '@/Components/Modal';
import { PageProps } from '@/types';
import { Head, router, useForm, usePage } from '@inertiajs/react';
import { useState } from 'react';

type Device = { id:number; hwid:string; device_name?:string; status:string; last_seen_at?:string; project:{name:string}; user:{username:string}; license:{key:string} };
type LicenseOption = { id:number; key:string; user_id:number; user?:{username:string} };

export default function Index({items,licenses}:{items:{data:Device[]};licenses:LicenseOption[]}) {
    const role=usePage<PageProps>().props.auth.user.role;
    const area=role==='PARTNER'?'partner':'admin';
    const [open,setOpen]=useState(false);
    const {data,setData,post,processing,errors}=useForm({license_id:'',user_id:'',hwid:'',device_name:'',status:'active'});
    const chooseLicense=(id:string)=>{const license=licenses.find(item=>String(item.id)===id);setData({...data,license_id:id,user_id:license?String(license.user_id):''})};
    const submit=(event:React.FormEvent)=>{event.preventDefault();post(route(`${area}.devices.store`),{onSuccess:()=>setOpen(false)})};

    return <AppShell title="Dispositivos"><Head title="Dispositivos"/>
        {role!=='CLIENT'&&<div className="mb-5 flex justify-end"><button className="btn-primary" onClick={()=>setOpen(true)}>Registrar dispositivo</button></div>}
        <DataTable items={items.data} columns={[
            {label:'Dispositivo',render:d=><div><b>{d.device_name??'Sin nombre'}</b><code className="block text-xs text-cyan-300">{d.hwid}</code></div>},
            {label:'Cliente',render:d=>d.user.username},{label:'Licencia',render:d=><code>{d.license.key}</code>},{label:'Proyecto',render:d=>d.project.name},
            {label:'Estado',render:d=><span className="badge">{d.status}</span>},{label:'Última conexión',render:d=>d.last_seen_at?new Date(d.last_seen_at).toLocaleString():'—'},
            {label:'Acciones',render:d=>role==='CLIENT'?'—':<div className="flex gap-2"><button className="text-amber-300" onClick={()=>router.put(route(`${area}.devices.update`,d.id),{status:d.status==='blocked'?'active':'blocked'})}>{d.status==='blocked'?'Desbloquear':'Bloquear'}</button><button className="text-red-300" onClick={()=>confirm('¿Resetear dispositivo?')&&router.delete(route(`${area}.devices.destroy`,d.id))}>Reset</button></div>},
        ]}/>
        <Modal open={open} onClose={()=>setOpen(false)} title="Registrar dispositivo"><form onSubmit={submit} className="space-y-4">
            <Select label="Licencia asignada" value={data.license_id} onChange={e=>chooseLicense(e.target.value)} required><option value="">Seleccionar</option>{licenses.map(l=><option key={l.id} value={l.id}>{l.key} · {l.user?.username}</option>)}</Select>
            <Field label="HWID" value={data.hwid} onChange={e=>setData('hwid',e.target.value)} required/><Field label="Nombre" value={data.device_name} onChange={e=>setData('device_name',e.target.value)}/>
            <Select label="Estado" value={data.status} onChange={e=>setData('status',e.target.value)}><option value="active">Activo</option><option value="blocked">Bloqueado</option></Select>
            <p className="text-sm text-red-400">{Object.values(errors)[0]}</p><button className="btn-primary w-full" disabled={processing}>Registrar</button>
        </form></Modal>
    </AppShell>;
}
