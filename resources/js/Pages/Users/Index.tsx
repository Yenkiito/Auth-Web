import AppShell from "@/Components/AppShell";
import { Field } from "@/Components/FormField";
import Modal from "@/Components/Modal";
import { PageProps } from "@/types";
import { Head, useForm, usePage } from "@inertiajs/react";
import { Plus, Search, ShieldCheck } from "lucide-react";
import { useState } from "react";

type Client = {
    id: number;
    username: string;
    status: string;
    expires_at?: string;
    hwid_affected: boolean;
    last_login_at?: string;
    created_at: string;
};

export default function Index({ items }: { items: { data: Client[] } }) {
    const role = usePage<PageProps>().props.auth.user.role;
    const area = role === "PARTNER" ? "partner" : "admin";
    const [open, setOpen] = useState(false);
    const [search, setSearch] = useState("");
    const { data, setData, post, processing, errors, reset } = useForm({
        username: "",
        password: "",
        expiration: "",
        hwid_affected: true,
    });
    const submit = (event: React.FormEvent) => {
        event.preventDefault();
        post(route(`${area}.users.store`), {
            onSuccess: () => {
                setOpen(false);
                reset();
            },
        });
    };
    const filtered = items.data.filter((user) =>
        user.username.toLowerCase().includes(search.toLowerCase()),
    );
    return (
        <AppShell title="Users">
            <Head title="Users" />
            <div className="panel mb-5 flex flex-wrap items-center justify-between gap-3 p-4">
                <div className="relative min-w-64 flex-1">
                    <Search
                        className="absolute left-3 top-2.5 text-slate-500"
                        size={17}
                    />
                    <input
                        className="field pl-10"
                        placeholder="Search users..."
                        value={search}
                        onChange={(e) => setSearch(e.target.value)}
                    />
                </div>
                <button className="btn-primary" onClick={() => setOpen(true)}>
                    <Plus size={16} className="mr-2" />
                    Create User
                </button>
            </div>
            <div className="grid gap-4 lg:grid-cols-2 2xl:grid-cols-3">
                {filtered.map((user) => {
                    const expired =
                        user.expires_at &&
                        new Date(user.expires_at) < new Date();
                    return (
                        <article key={user.id} className="panel p-5">
                            <div className="mb-4 flex items-start justify-between">
                                <div>
                                    <h3 className="font-bold">
                                        {user.username}
                                    </h3>
                                    <p className="text-xs text-slate-500">
                                        Created:{" "}
                                        {new Date(
                                            user.created_at,
                                        ).toLocaleString()}
                                    </p>
                                </div>
                                <span
                                    className={`badge ${expired || user.status !== "active" ? "text-red-300" : "text-emerald-300"}`}
                                >
                                    Status: {expired ? "Expired" : user.status}
                                </span>
                            </div>
                            <div className="grid grid-cols-2 gap-2 text-xs text-slate-400">
                                <span>Last Login</span>
                                <b className="text-right text-slate-200">
                                    {user.last_login_at
                                        ? new Date(
                                              user.last_login_at,
                                          ).toLocaleString()
                                        : "N/A"}
                                </b>
                                <span>Expiration</span>
                                <b className="text-right text-slate-200">
                                    {user.expires_at
                                        ? new Date(
                                              user.expires_at,
                                          ).toLocaleString()
                                        : "N/A"}
                                </b>
                                <span>HWID Affected</span>
                                <b className="text-right text-slate-200">
                                    {user.hwid_affected ? "Yes" : "No"}
                                </b>
                            </div>
                        </article>
                    );
                })}
            </div>
            {filtered.length === 0 && (
                <div className="panel p-10 text-center text-slate-500">
                    No users found.
                </div>
            )}
            <Modal
                open={open}
                onClose={() => setOpen(false)}
                title="Create user"
            >
                <form onSubmit={submit} className="space-y-5">
                    <Field
                        label="Username"
                        value={data.username}
                        onChange={(e) => setData("username", e.target.value)}
                        required
                    />
                    <Field
                        label="Password"
                        type="password"
                        value={data.password}
                        onChange={(e) => setData("password", e.target.value)}
                        required
                    />
                    <p className="-mt-3 text-xs text-slate-500">
                        Mínimo 8 caracteres.
                    </p>
                    <Field
                        label="Expiration"
                        type="date"
                        value={data.expiration}
                        onChange={(e) => setData("expiration", e.target.value)}
                        required
                    />
                    <label className="flex cursor-pointer items-center gap-3 rounded-xl border border-slate-800 p-4">
                        <input
                            type="checkbox"
                            checked={data.hwid_affected}
                            onChange={(e) =>
                                setData("hwid_affected", e.target.checked)
                            }
                            className="rounded border-slate-600 bg-slate-900 text-blue-600"
                        />
                        <ShieldCheck size={18} className="text-blue-400" />
                        <span className="text-sm font-medium">
                            HWID Affected
                        </span>
                    </label>
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
                            Create User
                        </button>
                    </div>
                </form>
            </Modal>
        </AppShell>
    );
}
