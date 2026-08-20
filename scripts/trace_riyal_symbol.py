from __future__ import annotations

import math
import sys
from collections import defaultdict
from pathlib import Path

from PIL import Image


def perpendicular_distance(point, start, end):
    if start == end:
        return math.dist(point, start)
    x, y = point
    x1, y1 = start
    x2, y2 = end
    return abs((y2 - y1) * x - (x2 - x1) * y + x2 * y1 - y2 * x1) / math.hypot(y2 - y1, x2 - x1)


def rdp(points, epsilon):
    if len(points) < 3:
        return points
    distances = [perpendicular_distance(point, points[0], points[-1]) for point in points[1:-1]]
    if not distances or max(distances) <= epsilon:
        return [points[0], points[-1]]
    index = distances.index(max(distances)) + 1
    return rdp(points[: index + 1], epsilon)[:-1] + rdp(points[index:], epsilon)


def simplify_closed(points, epsilon=1.35):
    if len(points) < 5:
        return points
    left = min(range(len(points)), key=lambda index: (points[index][0], points[index][1]))
    right = max(range(len(points)), key=lambda index: (points[index][0], points[index][1]))
    if left > right:
        left, right = right, left
    first = rdp(points[left : right + 1], epsilon)
    second = rdp(points[right:] + points[: left + 1], epsilon)
    return first[:-1] + second[:-1]


def trace(source: Path, target: Path):
    image = Image.open(source).convert("L")
    width, height = image.size
    pixels = image.load()
    black = {(x, y) for y in range(height) for x in range(width) if pixels[x, y] < 180}
    if not black:
        raise RuntimeError("No dark symbol pixels found.")

    edges = set()
    for x, y in black:
        if (x, y - 1) not in black:
            edges.add(((x, y), (x + 1, y)))
        if (x + 1, y) not in black:
            edges.add(((x + 1, y), (x + 1, y + 1)))
        if (x, y + 1) not in black:
            edges.add(((x + 1, y + 1), (x, y + 1)))
        if (x - 1, y) not in black:
            edges.add(((x, y + 1), (x, y)))

    outgoing = defaultdict(list)
    for start, end in edges:
        outgoing[start].append(end)

    unused = set(edges)
    loops = []
    while unused:
        start_edge = min(unused)
        start, current = start_edge
        unused.remove(start_edge)
        loop = [start, current]
        while current != start:
            candidates = [end for end in outgoing[current] if (current, end) in unused]
            if not candidates:
                break
            following = candidates[0]
            unused.remove((current, following))
            current = following
            loop.append(current)
        if current == start and len(loop) > 20:
            loops.append(loop[:-1])

    loops = [simplify_closed(loop) for loop in loops]
    loops = [loop for loop in loops if len(loop) >= 3]
    min_x = min(x for loop in loops for x, _ in loop)
    min_y = min(y for loop in loops for _, y in loop)
    max_x = max(x for loop in loops for x, _ in loop)
    max_y = max(y for loop in loops for _, y in loop)

    paths = []
    for loop in loops:
        commands = [f"M {loop[0][0] - min_x} {loop[0][1] - min_y}"]
        commands.extend(f"L {x - min_x} {y - min_y}" for x, y in loop[1:])
        commands.append("Z")
        paths.append(" ".join(commands))

    svg = (
        f'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 {max_x - min_x} {max_y - min_y}" '
        'role="img" aria-label="Saudi Riyal" fill="currentColor">\n'
        f'  <path fill-rule="evenodd" d="{" ".join(paths)}"/>\n'
        '</svg>\n'
    )
    target.parent.mkdir(parents=True, exist_ok=True)
    target.write_text(svg, encoding="utf-8")


if __name__ == "__main__":
    if len(sys.argv) != 3:
        raise SystemExit("Usage: trace_riyal_symbol.py SOURCE_IMAGE TARGET_SVG")
    trace(Path(sys.argv[1]), Path(sys.argv[2]))
