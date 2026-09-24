import { Field } from "@/Components/FormField";
import AppShell from "@/Components/AppShell";
import Modal from "@/Components/Modal";
import { Head, Link, router, useForm } from "@inertiajs/react";
import { Check, Clipboard, Code2, Pause, Plus, RefreshCw, Trash2 } from "lucide-react";
import { useMemo, useState } from "react";

type Project = {
    id: number;
    name: string;
    description?: string;
    status: string;
    version: string;
    key_prefix: string;
    owner_id: string;
    application_secret: string;
    api_url: string;
    partners_count: number;
    users_count: number;
    licenses_count: number;
};

export default function Index({
    items,
    selected,
}: {
    items: Project[];
    selected: Project | null;
}) {
    const [open, setOpen] = useState(false);
    const [showCode, setShowCode] = useState(false);
    const [copied, setCopied] = useState("");
    const [deleteTarget, setDeleteTarget] = useState<Project | null>(null);
    const [rotateTarget, setRotateTarget] = useState<Project | null>(null);
    const { data, setData, post, processing, errors, reset } = useForm({
        name: "",
        description: "",
        version: "1.0",
        key_prefix: "",
        status: "active",
    });
    const code = useMemo(
        () =>
            selected
                ? `std::string name = skCrypt("${selected.name}").decrypt();\nstd::string ownerid = skCrypt("${selected.owner_id}").decrypt();\nstd::string version = skCrypt("${selected.version}").decrypt();\nstd::string url = skCrypt("${selected.api_url}").decrypt();\nstd::string path = skCrypt("").decrypt();`
                : "",
        [selected],
    );
    const copy = (label: string, value: string) => {
        navigator.clipboard.writeText(value);
        setCopied(label);
        setTimeout(() => setCopied(""), 1400);
    };
    const submit = (event: React.FormEvent) => {
        event.preventDefault();
        post(route("admin.projects.store"), {
            onSuccess: () => {
                setOpen(false);
                reset();
            },
        });
    };
    const stats = [
        ["Total Apps", items.length],
        ["Active", items.filter((p) => p.status === "active").length],
        ["Paused", items.filter((p) => p.status !== "active").length],
        ["Users", selected?.users_count ?? 0],
    ];
    const deleteForm = useForm({});
    const rotateForm = useForm({});
    const deleteApplication = () => {
        if (!deleteTarget) return;
        deleteForm.delete(route("admin.projects.destroy", deleteTarget.id), {
            onSuccess: () => setDeleteTarget(null),
        });
    };
    const rotateSecret = () => {
        if (!rotateTarget) return;
        rotateForm.post(route("admin.projects.rotate", rotateTarget.id), {
            onSuccess: () => setRotateTarget(null),
        });
    };

    return (
        <AppShell title="Manage Applications">
            <Head title="Manage Applications" />
            <div className="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                {stats.map(([label, value]) => (
                    <div key={label} className="panel p-5 text-center">
                        <strong className="text-3xl">{value}</strong>
                        <p className="mt-1 text-sm text-slate-400">{label}</p>
                    </div>
                ))}
            </div>
            <div className="grid gap-6 xl:grid-cols-[minmax(320px,.8fr)_1.5fr]">
                <section className="panel p-5">
                    <div className="mb-5 flex items-center justify-between">
                        <div>
                            <h2 className="font-semibold">
                                Application Credentials
                            </h2>
                            <p className="text-xs text-slate-500">
                                Credenciales y ejemplo de inicialización
                            </p>
                        </div>
                        <button
                            className={`h-6 w-11 rounded-full p-1 transition ${showCode ? "bg-blue-600" : "bg-slate-700"}`}
                            onClick={() => setShowCode(!showCode)}
                        >
                            <span
                                className={`block h-4 w-4 rounded-full bg-white transition ${showCode ? "translate-x-5" : ""}`}
                            />
                        </button>
                    </div>
                    {!selected ? (
                        <p className="text-sm text-slate-500">
                            Crea una aplicación para comenzar.
                        </p>
                    ) : showCode ? (
                        <div>
                            <div className="mb-3 flex items-center justify-between">
                                <span className="badge">
                                    <Code2 size={13} className="mr-1" />
                                    C++
                                </span>
                                <button
                                    className="text-sm text-cyan-300"
                                    onClick={() => copy("code", code)}
                                >
                                    {copied === "code"
                                        ? "Copiado"
                                        : "Copiar código"}
                                </button>
                            </div>
                            <pre className="overflow-x-auto rounded-xl border border-slate-800 bg-black/40 p-4 text-xs leading-6 text-slate-300">
                                <code>{code}</code>
                            </pre>
                        </div>
                    ) : (
                        <div className="space-y-3">
                            {[
                                ["Application Name", selected.name],
                                ["Account Owner ID", selected.owner_id],
                                [
                                    "Application Secret",
                                    selected.application_secret,
                                ],
                                ["Application Version", selected.version],
                                ["Key Prefix", selected.key_prefix],
                            ].map(([label, value]) => (
                                <div
                                    key={label}
                                    className="rounded-xl border border-slate-800 bg-slate-950/60 p-4"
                                >
                                    <p className="mb-1 text-[10px] font-bold uppercase tracking-wider text-slate-500">
                                        {label}
                                    </p>
                                    <div className="flex items-center justify-between gap-3">
                                        <code className="truncate text-sm text-slate-200">
                                            {value}
                                        </code>
                                        <button
                                            onClick={() => copy(label, value)}
                                            className="text-slate-500 hover:text-cyan-300"
                                        >
                                            {copied === label ? (
                                                <Check size={16} />
                                            ) : (
                                                <Clipboard size={16} />
                                            )}
                                        </button>
                                    </div>
                                </div>
                            ))}
                            <button
                                className="btn-secondary w-full text-amber-300"
                                onClick={() => setRotateTarget(selected)}
                            >
                                <RefreshCw size={15} className="mr-2" />
                                Refresh Application Secret
                            </button>
                        </div>
                    )}
                </section>
                <section className="panel p-5">
                    <div className="mb-4 flex items-center justify-between">
                        <h2 className="font-semibold">My Applications</h2>
                        <button
                            className="btn-primary"
                            onClick={() => setOpen(true)}
                        >
                            <Plus size={16} className="mr-2" />
                            Create Application
                        </button>
                    </div>
                    <div className="space-y-3">
                        {items.map((app) => (
                            <div
                                key={app.id}
                                className={`rounded-xl border p-5 ${app.id === selected?.id ? "border-blue-500/60 bg-blue-500/5" : "border-slate-800 bg-slate-950/40"}`}
                            >
                                <div className="flex flex-wrap items-start justify-between gap-4">
                                    <div>
                                        <h3 className="text-lg font-bold">
                                            {app.name}
                                        </h3>
                                        <p className="text-sm text-slate-500">
                                            Version {app.version} ·{" "}
                                            {app.users_count} users ·{" "}
                                            {app.licenses_count} licenses
                                        </p>
                                    </div>
                                    <span
                                        className={`badge ${app.status === "active" ? "text-emerald-300" : "text-amber-300"}`}
                                    >
                                        {app.status.toUpperCase()}
                                    </span>
                                </div>
                                <div className="mt-4 flex flex-wrap gap-2">
                                    {app.id === selected?.id ? (
                                        <span className="btn bg-emerald-700/30 text-emerald-300">
                                            <Check size={15} className="mr-2" />
                                            Selected
                                        </span>
                                    ) : (
                                        <Link
                                            as="button"
                                            method="post"
                                            href={route(
                                                "admin.projects.select",
                                                app.id,
                                            )}
                                            className="btn-primary"
                                        >
                                            Select
                                        </Link>
                                    )}
                                    <button
                                        className="btn-secondary"
                                        onClick={() =>
                                            router.post(
                                                route(
                                                    "admin.projects.toggle",
                                                    app.id,
                                                ),
                                            )
                                        }
                                    >
                                        <Pause size={15} className="mr-2" />
                                        {app.status === "active"
                                            ? "Pause"
                                            : "Activate"}
                                    </button>
                                    <button className="inline-flex items-center rounded-xl border border-red-500/40 px-4 py-2 text-sm font-semibold text-red-300 hover:bg-red-500/10" onClick={() => setDeleteTarget(app)}>
                                        <Trash2 size={15} className="mr-2" />Delete
                                    </button>
                                </div>
                            </div>
                        ))}
                    </div>
                </section>
            </div>
            <Modal
                open={open}
                onClose={() => setOpen(false)}
                title="Create New Application"
            >
                <form onSubmit={submit} className="space-y-4">
                    <Field
                        label="Application name"
                        value={data.name}
                        onChange={(e) => setData("name", e.target.value)}
                        required
                    />
                    <Field
                        label="Key Prefix"
                        placeholder="KNYR"
                        maxLength={10}
                        value={data.key_prefix}
                        onChange={(e) =>
                            setData("key_prefix", e.target.value.toUpperCase())
                        }
                        required
                    />
                    <label className="block text-sm text-slate-400">
                        Application description
                        <textarea
                            className="field mt-1.5 min-h-24"
                            maxLength={500}
                            value={data.description}
                            onChange={(e) =>
                                setData("description", e.target.value)
                            }
                        />
                        <span className="text-xs text-slate-600">
                            Max. 500 caracteres
                        </span>
                    </label>
                    <Field
                        label="Version"
                        value={data.version}
                        onChange={(e) => setData("version", e.target.value)}
                        required
                    />
                    <p className="text-sm text-red-400">
                        {Object.values(errors)[0]}
                    </p>
                    <div className="flex justify-end gap-3">
                        <button
                            type="button"
                            className="btn-secondary"
                            onClick={() => setOpen(false)}
                        >
                            Cancel
                        </button>
                        <button disabled={processing} className="btn-primary">
                            Create
                        </button>
                    </div>
                </form>
            </Modal>
            <Modal open={deleteTarget !== null} onClose={() => !deleteForm.processing && setDeleteTarget(null)} title="Delete Application" closeable={!deleteForm.processing}>
                <p className="text-sm text-slate-300">¿Eliminar <strong>{deleteTarget?.name}</strong>? La aplicación dejará de funcionar, sus sesiones API se cerrarán y las cuentas asociadas serán bloqueadas.</p>
                <div className="mt-6 flex justify-end gap-3">
                    <button className="btn-secondary" disabled={deleteForm.processing} onClick={() => setDeleteTarget(null)}>Cancel</button>
                    <button className="rounded-xl bg-red-500 px-4 py-2 font-semibold text-white disabled:opacity-50" disabled={deleteForm.processing} onClick={deleteApplication}>{deleteForm.processing ? "Deleting..." : "Delete Application"}</button>
                </div>
            </Modal>
            <Modal open={rotateTarget !== null} onClose={() => !rotateForm.processing && setRotateTarget(null)} title="Refresh Application Secret" closeable={!rotateForm.processing}>
                <p className="text-sm text-slate-300">¿Regenerar el secreto de <strong>{rotateTarget?.name}</strong>? El secreto anterior quedará invalidado.</p>
                <div className="mt-6 flex justify-end gap-3">
                    <button className="btn-secondary" disabled={rotateForm.processing} onClick={() => setRotateTarget(null)}>Cancel</button>
                    <button className="rounded-xl bg-amber-500 px-4 py-2 font-semibold text-slate-950 disabled:opacity-50" disabled={rotateForm.processing} onClick={rotateSecret}>{rotateForm.processing ? "Refreshing..." : "Refresh Secret"}</button>
                </div>
            </Modal>
        </AppShell>
    );
}
