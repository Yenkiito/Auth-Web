import AppShell from "@/Components/AppShell";
import DataTable from "@/Components/DataTable";
import Modal from "@/Components/Modal";
import { Field, Select } from "@/Components/FormField";
import { Head, useForm, usePage } from "@inertiajs/react";
import { useState } from "react";
import { PageProps } from "@/types";
type Partner = {
    id: number;
    name: string;
    project_id: number;
    parent_id?: number;
    status: string;
    children_count: number;
    clients_count: number;
    licenses_count: number;
    account: { username: string; email?: string };
    project: { name: string };
};
export default function Index({
    items,
}: {
    items: { data: Partner[] };
}) {
    const role = usePage<PageProps>().props.auth.user.role;
    const [open, setOpen] = useState(false);
    const { data, setData, post, processing, errors } = useForm({
        parent_id: "",
        name: "",
        username: "",
        email: "",
        password: "",
        password_confirmation: "",
        status: "active",
    });
    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post(
            route(`${role === "PARTNER" ? "partner" : "admin"}.partners.store`),
            { onSuccess: () => setOpen(false) },
        );
    };
    return (
        <AppShell title="Socios">
            <Head title="Socios" />
            <div className="mb-5 flex justify-end">
                <button className="btn-primary" onClick={() => setOpen(true)}>
                    Nuevo socio
                </button>
            </div>
            <DataTable
                items={items.data}
                columns={[
                    {
                        label: "Socio",
                        render: (p) => (
                            <div>
                                <b>{p.name}</b>
                                <div className="text-xs text-slate-500">
                                    @{p.account.username}
                                </div>
                            </div>
                        ),
                    },
                    { label: "Proyecto", render: (p) => p.project?.name },
                    {
                        label: "Nivel",
                        render: (p) => (p.parent_id ? "Subsocio" : "Principal"),
                    },
                    {
                        label: "Estado",
                        render: (p) => (
                            <span className="badge">{p.status}</span>
                        ),
                    },
                    { label: "Descendientes", render: (p) => p.children_count },
                    { label: "Clientes", render: (p) => p.clients_count },
                    { label: "Licencias", render: (p) => p.licenses_count },
                ]}
            />
            <Modal
                open={open}
                onClose={() => setOpen(false)}
                title="Crear socio"
            >
                <form onSubmit={submit} className="grid gap-4 sm:grid-cols-2">
                    <Select
                        label="Socio padre"
                        value={data.parent_id}
                        onChange={(e) => setData("parent_id", e.target.value)}
                    >
                        <option value="">Sin padre</option>
                        {items.data.map((p) => (
                            <option key={p.id} value={p.id}>
                                {p.name}
                            </option>
                        ))}
                    </Select>
                    <Field
                        label="Nombre"
                        value={data.name}
                        onChange={(e) => setData("name", e.target.value)}
                        required
                    />
                    <Field
                        label="Usuario"
                        value={data.username}
                        onChange={(e) => setData("username", e.target.value)}
                        required
                    />
                    <Field
                        label="Email"
                        type="email"
                        value={data.email}
                        onChange={(e) => setData("email", e.target.value)}
                    />
                    <Select
                        label="Estado"
                        value={data.status}
                        onChange={(e) => setData("status", e.target.value)}
                    >
                        <option value="active">Activo</option>
                        <option value="blocked">Bloqueado</option>
                    </Select>
                    <Field
                        label="Contraseña"
                        type="password"
                        value={data.password}
                        onChange={(e) => setData("password", e.target.value)}
                        required
                    />
                    <Field
                        label="Confirmar"
                        type="password"
                        value={data.password_confirmation}
                        onChange={(e) =>
                            setData("password_confirmation", e.target.value)
                        }
                        required
                    />
                    <p className="text-sm text-red-400 sm:col-span-2">
                        {Object.values(errors)[0]}
                    </p>
                    <button
                        disabled={processing}
                        className="btn-primary sm:col-span-2"
                    >
                        Crear socio
                    </button>
                </form>
            </Modal>
        </AppShell>
    );
}
