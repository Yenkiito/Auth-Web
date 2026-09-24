import { InputHTMLAttributes, SelectHTMLAttributes } from 'react';
export function Field({label,...props}:InputHTMLAttributes<HTMLInputElement>&{label:string}){return <label className="block text-sm text-slate-400">{label}<input {...props} className="field mt-1.5"/></label>}
export function Select({label,children,...props}:SelectHTMLAttributes<HTMLSelectElement>&{label:string}){return <label className="block text-sm text-slate-400">{label}<select {...props} className="field mt-1.5">{children}</select></label>}
