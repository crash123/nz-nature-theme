#!/usr/bin/env python3
"""Generate placeholder photographs for the NZ Nature seed galleries.

These stand in for real photos until you upload your own. They are rendered
from code (gradients + simple NZ motifs), so there are no licensing concerns.

Requires `rsvg-convert` (librsvg). Run from the repo root:

    python3 scripts/generate-placeholders.py

Output: theme/nz-nature/assets/images/placeholders/nz-photo-NN.png
"""

import math
import os
import shutil
import subprocess
import tempfile

ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
OUT_DIR = os.path.join(
    ROOT, "theme", "nz-nature", "assets", "images", "placeholders"
)

PALETTES = [
    ("alps", "#fefae0", "#e9edc9", "#40916c", "#0b2a20"),
    ("ocean", "#48cae4", "#1d6a96", "#1d6a96", "#0b2a20"),
    ("fern", "#e9edc9", "#40916c", "#1b4332", "#0b2a20"),
    ("kowhai", "#fefae0", "#e9c46a", "#bc6c25", "#7f4f24"),
    ("coast", "#48cae4", "#1d6a96", "#14809f", "#0b2a20"),
    ("dusk", "#e9c46a", "#bc6c25", "#1d6a96", "#0b2a20"),
]

SIZES = [
    (1600, 1067),
    (1067, 1600),
    (1600, 1067),
    (1200, 1200),
    (1600, 1067),
    (1067, 1600),
    (1600, 1067),
    (1200, 1200),
    (1067, 1600),
    (1600, 1067),
    (1600, 1067),
    (1200, 1200),
]

MOTIFS = ["mountains", "fern", "mountains", "stars", "waves", "mountains"]


def ridge(w, h, base, peaks, colour, opacity):
    pts = [f"0,{h}"]
    step = w / (len(peaks) - 1)
    for i, pk in enumerate(peaks):
        x = i * step
        y = base - pk * (h * 0.16)
        pts.append(f"{x:.0f},{y:.0f}")
    pts.append(f"{w},{h}")
    return (
        f'<polygon points="{" ".join(pts)}" fill="{colour}" '
        f'opacity="{opacity}"/>'
    )


def fern(w, h, colour):
    cx, cy = w * 0.78, h * 0.42
    scale = min(w, h) * 0.0009
    out = ['<g stroke="%s" stroke-width="%d" fill="none" stroke-linecap="round">'
           % (colour, max(3, int(6 * scale * 8)))]
    out.append(f'<path d="M{cx:.0f} {cy + 260 * scale * 8:.0f} '
               f'C{cx:.0f} {cy + 100 * scale * 8:.0f} {cx:.0f} '
               f'{cy - 120 * scale * 8:.0f} {cx:.0f} {cy - 260 * scale * 8:.0f}"/>')
    out.append("</g>")
    for i in range(10):
        t = i / 9.0
        y = cy + 240 * scale * 8 - t * 480 * scale * 8
        ln = (200 - t * 120) * scale * 8
        wd = ln * 0.42
        out.append(
            f'<ellipse cx="{cx - ln / 2:.0f}" cy="{y:.0f}" rx="{ln / 2:.0f}" '
            f'ry="{wd:.0f}" fill="{colour}" transform="rotate(-18 {cx:.0f} {y:.0f})"/>'
        )
        out.append(
            f'<ellipse cx="{cx + ln / 2:.0f}" cy="{y:.0f}" rx="{ln / 2:.0f}" '
            f'ry="{wd:.0f}" fill="{colour}" transform="rotate(18 {cx:.0f} {y:.0f})"/>'
        )
    return "".join(out)


def stars(w, h, colour):
    pts = [(0.30, 0.22), (0.62, 0.14), (0.74, 0.30), (0.52, 0.40), (0.55, 0.24)]
    out = []
    for fx, fy in pts:
        x, y = fx * w, fy * h
        r = min(w, h) * 0.014
        out.append(
            f'<path d="M{x:.0f} {y - r:.0f} L{x + r * 0.3:.0f} {y - r * 0.3:.0f} '
            f'L{x + r:.0f} {y:.0f} L{x + r * 0.3:.0f} {y + r * 0.3:.0f} '
            f'L{x:.0f} {y + r:.0f} L{x - r * 0.3:.0f} {y + r * 0.3:.0f} '
            f'L{x - r:.0f} {y:.0f} L{x - r * 0.3:.0f} {y - r * 0.3:.0f} Z" '
            f'fill="{colour}"/>'
        )
    return "".join(out)


def waves(w, h, colour):
    out = []
    for row in range(5):
        y = h * (0.72 + row * 0.05)
        amp = h * 0.012 * (1 + row * 0.4)
        d = [f"M0 {y:.0f}"]
        x = 0.0
        while x < w:
            nx = x + w / 12
            d.append(
                f"Q{(x + nx) / 2:.0f} {y - amp:.0f} {nx:.0f} {y:.0f}"
            )
            x = nx
        out.append(
            f'<path d="{" ".join(d)}" fill="none" stroke="{colour}" '
            f'stroke-width="{max(2, int(h * 0.006))}" opacity="0.8"/>'
        )
    return "".join(out)


def make_svg(w, h, palette, motif, index):
    name, c1, c2, c3, c4 = palette
    horizon = h * (0.58 if h >= w else 0.66)
    sun_r = min(w, h) * 0.075
    sun_x, sun_y = w * 0.76, horizon - min(w, h) * 0.14

    parts = [
        f'<svg xmlns="http://www.w3.org/2000/svg" width="{w}" height="{h}" '
        f'viewBox="0 0 {w} {h}">',
        "<defs>",
        f'<linearGradient id="sky" x1="0" y1="0" x2="0" y2="1">'
        f'<stop offset="0" stop-color="{c1}"/>'
        f'<stop offset="0.6" stop-color="{c2}"/>'
        f'<stop offset="1" stop-color="{c3}"/></linearGradient>',
        "</defs>",
        f'<rect width="{w}" height="{h}" fill="url(#sky)"/>',
        f'<circle cx="{sun_x:.0f}" cy="{sun_y:.0f}" r="{sun_r:.0f}" '
        f'fill="{c1}" opacity="0.85"/>',
    ]

    if motif == "stars":
        parts.append(stars(w, h, c1))
    if motif == "fern":
        parts.append(fern(w, h, c2))

    parts.append(ridge(w, h, horizon + h * 0.02,
                       [0, 0.55, 0.25, 0.8, 0.35, 0.62, 0, 0.5], c3, 0.55))
    parts.append(ridge(w, h, horizon + h * 0.10,
                       [0, 0.4, 0.7, 0.2, 0.55, 0.95, 0.3, 0], c3, 0.85))
    parts.append(ridge(w, h, horizon + h * 0.20,
                       [0, 0.3, 0.15, 0.5, 0.25, 0.6, 0.35, 0], c4, 1))
    snow = (
        f'<polygon points="{w * 0.5:.0f},{horizon + h * 0.10 - h * 0.16 * 0.95:.0f} '
        f'{w * 0.46:.0f},{horizon + h * 0.055:.0f} {w * 0.5:.0f},{horizon + h * 0.075:.0f} '
        f'{w * 0.54:.0f},{horizon + h * 0.055:.0f}" fill="{c1}"/>'
    )
    parts.append(snow)

    if motif == "waves":
        parts.append(waves(w, h, c1))

    label = f"NZ Nature · {index + 1:02d}"
    parts.append(
        f'<text x="{w * 0.06:.0f}" y="{h * 0.94:.0f}" '
        f'font-family="Georgia, serif" font-size="{max(20, int(min(w, h) * 0.032))}" '
        f'fill="{c1}" opacity="0.9">{label}</text>'
    )
    parts.append("</svg>")
    return "".join(parts)


def main():
    rsvg = shutil.which("rsvg-convert")
    if not rsvg:
        raise SystemExit("rsvg-convert not found. Install librsvg (brew install librsvg).")

    os.makedirs(OUT_DIR, exist_ok=True)
    with tempfile.TemporaryDirectory() as tmp:
        for i in range(len(SIZES)):
            w, h = SIZES[i]
            palette = PALETTES[i % len(PALETTES)]
            motif = MOTIFS[i % len(MOTIFS)]
            svg = make_svg(w, h, palette, motif, i)
            svg_path = os.path.join(tmp, f"photo-{i}.svg")
            with open(svg_path, "w") as fh:
                fh.write(svg)
            out = os.path.join(OUT_DIR, f"nz-photo-{i + 1:02d}.png")
            subprocess.run([rsvg, "-w", str(w), "-h", str(h), svg_path, "-o", out], check=True)
            print(f"  {os.path.relpath(out, ROOT)} ({w}x{h}, {palette[0]}, {motif})")


if __name__ == "__main__":
    main()
