import { PageProps } from "@/types";
import { Link, usePage } from "@inertiajs/react";
import {
    Activity,
    ChevronDown,
    Cpu,
    FolderKanban,
    Gauge,
    KeyRound,
    LogOut,
    Settings,
    Users,
    UserRound,
} from "lucide-react";
import { PropsWithChildren, useState } from "react";

const menus = {
    admin: [
        ["Dashboard", "dashboard", Gauge],
        ["Manage Apps", "projects", FolderKanban],
        ["Partners", "partners", Users],
        ["Users", "users", UserRound],
        ["Licenses", "licenses", KeyRound],
        ["Devices", "devices", Cpu],
        ["Logs", "logs", Activity],
        ["Settings", "settings", Settings],
    ],
    partner: [
        ["Dashboard", "dashboard", Gauge],
        ["Mis socios", "partners", Users],
        ["Mis clientes", "users", UserRound],
        ["Mis licencias", "licenses", KeyRound],
        ["Mis dispositivos", "devices", Cpu],
        ["Actividad", "logs", Activity],
    ],
    client: [
        ["Dashboard", "dashboard", Gauge],
        ["Mis licencias", "licenses", KeyRound],
        ["Mis dispositivos", "devices", Cpu],
    ],
} as const;

export default function AppShell({
    children,
    title,
}: PropsWithChildren<{ title: string }>) {
    const { auth, flash, applicationContext } = usePage<PageProps>().props;
    const [appsOpen, setAppsOpen] = useState(false);
    const area =
        auth.user.role === "PARTNER"
            ? "partner"
            : auth.user.role === "CLIENT"
              ? "client"
              : "admin";
    return (
        <div className="min-h-screen bg-[radial-gradient(circle_at_top_right,_rgba(34,211,238,.10),_transparent_28%)]">
            <aside className="fixed inset-y-0 left-0 hidden w-64 overflow-y-auto border-r border-slate-800 bg-slate-950/95 p-5 backdrop-blur md:block">
                <div className="mb-6 flex items-center gap-3">
                    <div className="grid h-10 w-10 place-items-center rounded-xl bg-cyan-400 font-black text-slate-950">
                        K
                    </div>
                    <div>
                        <div className="font-bold tracking-wide">KENYRA</div>
                        <div className="text-xs text-slate-500">
                            LICENSE CONTROL
                        </div>
                    </div>
                </div>
                {applicationContext.active && (
                    <div className="mb-5">
                        <button
                            onClick={() => setAppsOpen(!appsOpen)}
                            className="flex w-full items-center justify-between rounded-xl border border-cyan-500/30 bg-cyan-500/10 px-3 py-3 text-left text-sm font-semibold text-cyan-300"
                        >
                            <span className="truncate">
                                {applicationContext.active.name}
                            </span>
                            <ChevronDown size={16} />
                        </button>
                        {appsOpen && area === "admin" && (
                            <div className="mt-2 space-y-1 rounded-xl border border-slate-800 bg-slate-900 p-2">
                                {applicationContext.projects.map((app) => (
                                    <Link
                                        key={app.id}
                                        as="button"
                                        method="post"
                                        href={route(
                                            "admin.projects.select",
                                            app.id,
                                        )}
                                        className={`flex w-full items-center justify-between rounded-lg px-3 py-2 text-left text-sm ${app.id === applicationContext.active?.id ? "bg-blue-600 text-white" : "text-slate-300 hover:bg-slate-800"}`}
                                    >
                                        <span>{app.name}</span>
                                        <span
                                            className={`h-2 w-2 rounded-full ${app.status === "active" ? "bg-emerald-400" : "bg-amber-400"}`}
                                        />
                                    </Link>
                                ))}
                            </div>
                        )}
                    </div>
                )}
                <nav className="space-y-1">
                    {menus[area].map(([label, path, Icon]) => {
                        const href = route(
                            `${area}.${path}${path === "dashboard" || path === "settings" ? "" : ".index"}`,
                        );
                        return (
                            <Link
                                key={path}
                                href={href}
                                className={`flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm ${location.pathname.startsWith(`/${area}/${path}`) ? "bg-cyan-400/10 text-cyan-300" : "text-slate-400 hover:bg-slate-900 hover:text-white"}`}
                            >
                                <Icon size={18} />
                                {label}
                            </Link>
                        );
                    })}
                </nav>
                <div className="mt-8 border-t border-slate-800 pt-4">
                    <div className="mb-1 text-sm font-medium">
                        {auth.user.name}
                    </div>
                    <div className="mb-3 text-xs text-slate-500">
                        {auth.user.role} · @{auth.user.username}
                    </div>
                    <Link
                        method="post"
                        as="button"
                        href={route("logout")}
                        className="flex items-center gap-2 text-sm text-slate-400 hover:text-red-300"
                    >
                        <LogOut size={16} />
                        Cerrar sesión
                    </Link>
                </div>
            </aside>
            <main className="md:pl-64">
                <header className="sticky top-0 z-20 flex items-center justify-between border-b border-slate-800 bg-slate-950/70 px-5 py-4 backdrop-blur md:px-8">
                    <div>
                        <h1 className="text-xl font-semibold">{title}</h1>
                        {applicationContext.active && (
                            <p className="text-xs text-slate-500">
                                Aplicación actual:{" "}
                                {applicationContext.active.name}
                            </p>
                        )}
                    </div>
                </header>
                <div className="p-5 md:p-8">
                    {flash.success && (
                        <div className="mb-5 rounded-xl border border-emerald-700/50 bg-emerald-500/10 p-3 text-sm text-emerald-300">
                            {flash.success}
                        </div>
                    )}
                    {flash.project_key && (
                        <div className="mb-5 rounded-xl border border-amber-600/50 bg-amber-500/10 p-3 font-mono text-amber-200">
                            Application Secret: {flash.project_key}
                        </div>
                    )}
                    {children}
                </div>
            </main>
        </div>
    );
}
