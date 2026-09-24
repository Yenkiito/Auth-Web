import { SVGAttributes } from 'react';

export default function ApplicationLogo(props: SVGAttributes<SVGElement>) {
    return <svg {...props} viewBox="0 0 96 96" fill="none" xmlns="http://www.w3.org/2000/svg">
        <rect x="4" y="4" width="88" height="88" rx="24" fill="#22D3EE"/>
        <path d="M29 24v48h12V55l17 17h16L51 48l22-24H58L41 43V24H29Z" fill="#020617"/>
    </svg>;
}
