import AppShell from '@/Components/AppShell';
import { PageProps } from '@/types';
import { Head } from '@inertiajs/react';
import UpdatePasswordForm from './Partials/UpdatePasswordForm';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm';

export default function Edit({
    mustVerifyEmail,
    status,
}: PageProps<{ mustVerifyEmail: boolean; status?: string }>) {
    return (
        <AppShell title="Mi perfil">
            <Head title="Mi perfil" />
            <div className="max-w-3xl space-y-6">
                    <div className="panel p-4 sm:p-8">
                        <UpdateProfileInformationForm
                            mustVerifyEmail={mustVerifyEmail}
                            status={status}
                            className="max-w-xl"
                        />
                    </div>

                    <div className="panel p-4 sm:p-8">
                        <UpdatePasswordForm className="max-w-xl" />
                    </div>

            </div>
        </AppShell>
    );
}
