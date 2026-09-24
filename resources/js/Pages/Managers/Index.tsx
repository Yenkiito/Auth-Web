import AppShell from "@/Components/AppShell";
import { Field } from "@/Components/FormField";
import Modal from "@/Components/Modal";
import { Head, useForm } from "@inertiajs/react";
import { Plus, Trash2 } from "lucide-react";
import { useState } from "react";

type Manager = {
    id: number;
    name: string;
    username: string;
    email?: string;
    status: string;
    managed_projects_count: number;
};

export default function Index({ items }: { items: { data: Manager[] } }) {
    const [createOpen, setCreateOpen] = useState(false);
    const [deleteTarget, setDeleteTarget] = useState<Manager | null>(null);
    const createForm = useForm({ name: "", username: "", email: "", password: "", password_confirmation: "" });
    const deleteForm = useForm({});

    const createManager = (event: React.FormEvent) => {
        event.preventDefault();
        createForm.post(route("admin.managers.store"), {
            onSuccess: () => { setCreateOpen(false); createForm.reset(); },
        });
    };
    const deleteManager = () => {
        if (!deleteTarget) return;
        deleteForm.delete(route("admin.managers.destroy", deleteTarget.id), {
            preserveScroll: true,
            onSuccess: () => setDeleteTarget(null),
        });
    };

    return (
        <AppShell title="Managers">
            <Head title="Managers" />
            <div className="mb-5 flex justify-end">
                <button className="btn-primary" onClick={() => setCreateOpen(true)}><Plus size={16} className="mr-2" />Nuevo manager</button>
            </div>
            <div className="grid gap-4 lg:grid-cols-2">
                {items.data.map((manager) => (
                    <article key={manager.id} className="panel p-5">
                        <div className="flex items-start justify-between gap-4">
                            <div><h2 className="font-bold">{manager.name}</h2><p className="text-sm text-slate-500">@{manager.username}</p><p className="mt-2 text-xs text-slate-400">{manager.managed_projects_count} aplicaciones</p></div>
                            <button type="button" onClick={() => setDeleteTarget(manager)} className="rounded-lg border border-red-500/40 p-2 text-red-300 hover:bg-red-500/10" title="Eliminar manager"><Trash2 size={17} /></button>
                        </div>
                    </article>
                ))}
            </div>
            {items.data.length === 0 && <div className="panel p-10 text-center text-slate-500">No hay managers creados.</div>}

            <Modal open={createOpen} onClose={() => setCreateOpen(false)} title="Crear manager">
                <form onSubmit={createManager} className="space-y-4">
                    <Field label="Nombre" value={createForm.data.name} onChange={(e) => createForm.setData("name", e.target.value)} required />
                    <Field label="Usuario" value={createForm.data.username} onChange={(e) => createForm.setData("username", e.target.value)} required />
                    <Field label="Email" type="email" value={createForm.data.email} onChange={(e) => createForm.setData("email", e.target.value)} />
                    <Field label="Contraseña" type="password" value={createForm.data.password} onChange={(e) => createForm.setData("password", e.target.value)} required />
                    <Field label="Confirmar contraseña" type="password" value={createForm.data.password_confirmation} onChange={(e) => createForm.setData("password_confirmation", e.target.value)} required />
                    <p className="text-xs text-slate-500">Se permite desde 1 carácter.</p>
                    <p className="text-sm text-red-400">{Object.values(createForm.errors)[0]}</p>
                    <div className="flex justify-end gap-3"><button type="button" className="btn-secondary" onClick={() => setCreateOpen(false)}>Cancelar</button><button disabled={createForm.processing} className="btn-primary">Crear manager</button></div>
                </form>
            </Modal>

            <Modal open={deleteTarget !== null} onClose={() => !deleteForm.processing && setDeleteTarget(null)} title="Eliminar manager" closeable={!deleteForm.processing}>
                <p className="text-sm text-slate-300">¿Eliminar a <strong>{deleteTarget?.name}</strong>? Solo será posible si no tiene aplicaciones.</p>
                <div className="mt-6 flex justify-end gap-3"><button className="btn-secondary" disabled={deleteForm.processing} onClick={() => setDeleteTarget(null)}>Cancelar</button><button className="rounded-xl bg-red-500 px-4 py-2 font-semibold text-white disabled:opacity-50" disabled={deleteForm.processing} onClick={deleteManager}>{deleteForm.processing ? "Eliminando..." : "Eliminar manager"}</button></div>
            </Modal>
        </AppShell>
    );
}
