import type { SVGAttributes } from 'react';

export default function AppLogoIcon(props: SVGAttributes<SVGElement>) {
    return (
        <svg {...props} viewBox="0 0 48 48" xmlns="http://www.w3.org/2000/svg">
            <g fill="currentColor">
                <ellipse
                    cx="24"
                    cy="10"
                    rx="6"
                    ry="10"
                    transform="rotate(0 24 24)"
                    opacity="0.9"
                />
                <ellipse
                    cx="24"
                    cy="10"
                    rx="6"
                    ry="10"
                    transform="rotate(72 24 24)"
                    opacity="0.85"
                />
                <ellipse
                    cx="24"
                    cy="10"
                    rx="6"
                    ry="10"
                    transform="rotate(144 24 24)"
                    opacity="0.9"
                />
                <ellipse
                    cx="24"
                    cy="10"
                    rx="6"
                    ry="10"
                    transform="rotate(216 24 24)"
                    opacity="0.85"
                />
                <ellipse
                    cx="24"
                    cy="10"
                    rx="6"
                    ry="10"
                    transform="rotate(288 24 24)"
                    opacity="0.9"
                />
                <circle cx="24" cy="24" r="4" opacity="0.95" />
            </g>
        </svg>
    );
}
