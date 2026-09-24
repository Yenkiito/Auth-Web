import ApplicationLogo from '@/Components/ApplicationLogo';
import { Link } from '@inertiajs/react';
import { PropsWithChildren } from 'react';

export default function Guest({ children }: PropsWithChildren) {
    return (
        <div className="flex min-h-screen flex-col items-center bg-slate-950 bg-[radial-gradient(circle_at_top,_rgba(34,211,238,.16),_transparent_35%)] px-4 pt-6 sm:justify-center sm:pt-0">
            <div>
                <Link href="/">
                    <ApplicationLogo className="h-20 w-20" />
                </Link>
            </div>

            <div className="panel mt-6 w-full overflow-hidden px-7 py-7 sm:max-w-md">
                {children}
            </div>
        </div>
    );
}
