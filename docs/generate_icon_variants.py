#!/usr/bin/env python3
"""
Generate light and dark SVG variants for theme switching.

Dark icons (light fills for DARK background):
  - fill:#30302F -> #C0B8D8 (light purple)
  - fill:#333333 -> #A89FC4 (light purple)
  - fill:#010101 -> #8A80A8 (light purple)
  - fill:#7A3D21 -> #C9A86C (gold)
  - fill:none    -> add stroke:#E8E4F0, stroke-width:2
  - fill:#D6D2D2 -> #E8E4F0 (lighter)
  - fill:#3B3133 -> #B0A8C4 (light purple)

Light icons (dark fills for LIGHT background):
  - fill:#30302F -> #1a1a2e
  - fill:#333333 -> #2d2d44
  - fill:#010101 -> #111111
  - fill:#7A3D21 -> #6B2F15
  - fill:none    -> add stroke:#1a1a2e, stroke-width:2
  - fill:#D6D2D2 -> stays #D6D2D2
  - fill:#3B3133 -> stays #3B3133
"""

import os
import re
import shutil

ICONS_DIR = os.path.join(os.path.dirname(__file__), "assets", "pages", "icons")

DARK_COLORS = {
    "#30302F": "#C0B8D8",
    "#333333": "#A89FC4",
    "#010101": "#8A80A8",
    "#7A3D21": "#C9A86C",
    "#D6D2D2": "#E8E4F0",
    "#3B3133": "#B0A8C4",
}

LIGHT_COLORS = {
    "#30302F": "#1a1a2e",
    "#333333": "#2d2d44",
    "#010101": "#111111",
    "#7A3D21": "#6B2F15",
    "#D6D2D2": "#D6D2D2",
    "#3B3133": "#3B3133",
}


def generate_variant(src_path, dst_path, color_map, add_stroke=False, stroke_color=None):
    with open(src_path, "r", encoding="utf-8") as f:
        content = f.read()

    # Replace fill colors
    for old_color, new_color in color_map.items():
        # Case insensitive replacement in style attributes
        content = re.sub(
            rf'fill:{re.escape(old_color)}',
            f'fill:{new_color}',
            content,
            flags=re.IGNORECASE
        )

    # Handle fill:none SVGs (like magic_eye.svg) - add strokes
    if add_stroke and stroke_color:
        # For fill:none paths, add stroke
        content = content.replace(
            'style="fill:none;"',
            f'style="fill:none;stroke:{stroke_color};stroke-width:2;stroke-linecap:round;stroke-linejoin:round;"'
        )
        content = content.replace(
            "style='fill:none;'",
            f"style='fill:none;stroke:{stroke_color};stroke-width:2;stroke-linecap:round;stroke-linejoin:round;'"
        )

    with open(dst_path, "w", encoding="utf-8") as f:
        f.write(content)

    print(f"  Created: {os.path.basename(dst_path)}")


def main():
    icons = [
        "hero_skull.svg",
        "king_skull.svg",
        "skull_1.svg",
        "penta.svg",
        "magic_hand.svg",
        "magic_eye.svg",
    ]

    print("Generating SVG variants for theme switching...\n")

    for icon in icons:
        src = os.path.join(ICONS_DIR, icon)
        if not os.path.exists(src):
            print(f"  WARNING: {icon} not found, skipping")
            continue

        name, ext = os.path.splitext(icon)

        # Dark variant (original colors)
        dark_dst = os.path.join(ICONS_DIR, f"{name}-dark{ext}")
        generate_variant(src, dark_dst, DARK_COLORS,
                        add_stroke=(icon == "magic_eye.svg"),
                        stroke_color="#D6D2D2")

        # Light variant (inverted fills)
        light_dst = os.path.join(ICONS_DIR, f"{name}-light{ext}")
        generate_variant(src, light_dst, LIGHT_COLORS,
                        add_stroke=(icon == "magic_eye.svg"),
                        stroke_color="#1a1a2e")

    print("\nDone! Generated 12 SVG variant files.")


if __name__ == "__main__":
    main()
