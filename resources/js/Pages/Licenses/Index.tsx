import AppShell from "@/Components/AppShell";
import DataTable from "@/Components/DataTable";
import { Field, Select } from "@/Components/FormField";
import Modal from "@/Components/Modal";
import { PageProps } from "@/types";
import { Head, router, useForm, usePage } from "@inertiajs/react";
import { Plus } from "lucide-react";
import { useEffect, useState } from "react";

type License = {
    id: number;
    key: string;
    subscription: string;
    note?: string;
    status: string;
    expiry_unit: string;
    expiry_duration?: number;
    expires_at?: string;
    created_at: string;
    max_devices: number;
    user?: { id: number; username: string };
};

function buildMask(prefix?: string) {
    const base = (prefix || "KNYR").toUpperCase();
    return `${base}-****-****-****`;
}

export default function Index({ items }: { items: { data: License[] } }) {
    const { auth, applicationContext } = usePage<PageProps>().props;
    const area = auth.user.role === "PARTNER" ? "partner" : "admin";
    const canEdit = auth.user.role !== "CLIENT";
    const activePrefix = applicationContext.active?.key_prefix;
    const [open, setOpen] = useState(false);
    const { data, setData, post, processing, errors, reset } = useForm({
        quantity: "1",
        license_mask: buildMask(activePrefix),
        subscription: "default",
        note: "",
        expiry_unit: "days",
        expiry_duration: "30",
        lowercase: false,
        uppercase: true,
        max_devices: "1",
    });
    useEffect(() => {
        setData("license_mask", buildMask(activePrefix));
    }, [activePrefix]);
    const submit = (event: React.FormEvent) => {
        event.preventDefault();
        post(route(`${area}.licenses.bulk`), {
            onSuccess: () => {
                setOpen(false);
                reset();
                setData("license_mask", buildMask(activePrefix));
            },
        });
    };
    return (
        <AppShell title="Licenses">
            <Head title="Licenses" />
            {canEdit && (
                <div className="mb-5 flex justify-end">
                    <button
                        className="btn-primary"
                        onClick={() => setOpen(true)}
                    >
                        <Plus size={16} className="mr-2" />
                        Create License
                    </button>
                </div>
            )}
            <DataTable
                items={items.data}
                columns={[
                    {
                        label: "License Key",
                        render: (l) => (
                            <code className="text-cyan-300">{l.key}</code>
                        ),
                    },
                    {
                        label: "Subscription",
                        render: (l) => (
                            <span className="badge">{l.subscription}</span>
                        ),
                    },
                    {
                        label: "User",
                        render: (l) => l.user?.username ?? "Unassigned",
                    },
                    {
                        label: "Status",
                        render: (l) => (
                            <span
                                className={`badge ${l.status === "active" ? "text-emerald-300" : ""}`}
                            >
                                {l.status}
                            </span>
                        ),
                    },
                    {
                        label: "Expiration",
                        render: (l) =>
                            l.expires_at
                                ? new Date(l.expires_at).toLocaleString()
                                : "Lifetime",
                    },
                    {
                        label: "Actions",
                        render: (l) =>
                            canEdit ? (
                                <select
                                    className="field py-1"
                                    value={l.status}
                                    disabled={l.status === "revoked"}
                                    onChange={(e) =>
                                        router.put(
                                            route(
                                                `${area}.licenses.update`,
                                                l.id,
                                            ),
                                            {
                                                status: e.target.value,
                                                user_id: l.user?.id ?? null,
                                                expires_at: l.expires_at,
                                                max_devices: l.max_devices,
                                            },
                                        )
                                    }
                                >
                                    <option value="available">Available</option>
                                    <option value="active">Active</option>
                                    <option value="suspended">Suspended</option>
                                    <option value="expired">Expired</option>
                                    <option value="revoked">Revoked</option>
                                </select>
                            ) : (
                                "—"
                            ),
                    },
                ]}
            />
            <Modal
                open={open}
                onClose={() => setOpen(false)}
                title="Create a new license"
            >
                <form onSubmit={submit} className="space-y-4">
                    <Field
                        label="License Amount"
                        type="number"
                        min="1"
                        value={data.quantity}
                        onChange={(e) => setData("quantity", e.target.value)}
                        required
                    />
                    <Field
                        label="License Mask"
                        value={data.license_mask}
                        onChange={(e) =>
                            setData("license_mask", e.target.value)
                        }
                        required
                    />
                    <div className="flex gap-6 rounded-xl border border-slate-800 p-4">
                        <label className="flex items-center gap-2 text-sm">
                            <input
                                type="checkbox"
                                checked={data.lowercase}
                                onChange={(e) =>
                                    setData("lowercase", e.target.checked)
                                }
                            />
                            Lowercase Letters
                        </label>
                        <label className="flex items-center gap-2 text-sm">
                            <input
                                type="checkbox"
                                checked={data.uppercase}
                                onChange={(e) =>
                                    setData("uppercase", e.target.checked)
                                }
                            />
                            Uppercase Letters
                        </label>
                    </div>
                    <Select
                        label="Subscription Level"
                        value={data.subscription}
                        onChange={(e) =>
                            setData("subscription", e.target.value)
                        }
                    >
                        <option value="default">1 (default)</option>
                    </Select>
                    <label className="block text-sm text-slate-400">
                        License Note
                        <textarea
                            className="field mt-1.5 min-h-20"
                            value={data.note}
                            onChange={(e) => setData("note", e.target.value)}
                        />
                    </label>
                    <div className="grid grid-cols-2 gap-4">
                        <Select
                            label="Expiry Unit"
                            value={data.expiry_unit}
                            onChange={(e) =>
                                setData("expiry_unit", e.target.value)
                            }
                        >
                            <option value="seconds">Seconds</option>
                            <option value="minutes">Minutes</option>
                            <option value="hours">Hours</option>
                            <option value="days">Days</option>
                            <option value="weeks">Weeks</option>
                            <option value="months">Months</option>
                            <option value="years">Years</option>
                            <option value="lifetime">Lifetime</option>
                        </Select>
                        <Field
                            label="Expiry Duration"
                            type="number"
                            min="1"
                            disabled={data.expiry_unit === "lifetime"}
                            value={data.expiry_duration}
                            onChange={(e) =>
                                setData("expiry_duration", e.target.value)
                            }
                        />
                    </div>
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
                            Create License
                        </button>
                    </div>
                </form>
            </Modal>
        </AppShell>
    );
}