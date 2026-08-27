from pathlib import Path
import re
from PIL import Image, ImageDraw, ImageFont

ROOT = Path(__file__).resolve().parents[1]
IMAGES = ROOT / "images"
PIC = ROOT / "pic"
for directory in (IMAGES, PIC, PIC / "flag", PIC / "caticons", PIC / "staff", PIC / "rep", PIC / "smilies", PIC / "forumicons"):
    directory.mkdir(parents=True, exist_ok=True)

FONT_REGULAR = "/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf"
FONT_BOLD = "/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf"

def font(path, size):
    try:
        return ImageFont.truetype(path, size)
    except OSError:
        return ImageFont.load_default()

def gradient(size, top, bottom, horizontal=False):
    image = Image.new("RGB", size, top)
    pixels = image.load()
    span = max(1, (size[0] if horizontal else size[1]) - 1)
    for i in range(size[0] if horizontal else size[1]):
        ratio = i / span
        color = tuple(round(top[c] * (1 - ratio) + bottom[c] * ratio) for c in range(3))
        if horizontal:
            for y in range(size[1]):
                pixels[i, y] = color
        else:
            for x in range(size[0]):
                pixels[x, i] = color
    return image

def save_gradient(name, size, top, bottom, horizontal=False):
    gradient(size, top, bottom, horizontal).save(IMAGES / name, optimize=True)

def save_icon(name, kind, size=(24, 24), target=PIC):
    image = Image.new("RGBA", size, (0, 0, 0, 0))
    draw = ImageDraw.Draw(image)
    w, h = size
    navy = (29, 54, 82, 255)
    green = (82, 143, 108, 255)
    red = (180, 55, 55, 255)
    gold = (208, 159, 44, 255)
    if kind == "tick":
        draw.ellipse((1, 1, w - 2, h - 2), fill=green)
        draw.line((6, h // 2, 10, h - 6), fill="white", width=2)
        draw.line((10, h - 6, w - 5, 5), fill="white", width=2)
    elif kind == "cross":
        draw.ellipse((1, 1, w - 2, h - 2), fill=red)
        draw.line((6, 6, w - 6, h - 6), fill="white", width=2)
        draw.line((w - 6, 6, 6, h - 6), fill="white", width=2)
    elif kind == "star":
        points = []
        import math
        for index in range(10):
            angle = -math.pi / 2 + index * math.pi / 5
            radius = (h - 2) / 2 if index % 2 == 0 else (h - 2) / 4
            points.append((w / 2 + radius * math.cos(angle), h / 2 + radius * math.sin(angle)))
        draw.polygon(points, fill=gold)
    elif kind == "warning":
        draw.polygon(((w / 2, 1), (w - 2, h - 2), (2, h - 2)), fill=gold)
        draw.line((w / 2, 6, w / 2, h - 8), fill=navy, width=2)
        draw.ellipse((w / 2 - 1, h - 6, w / 2 + 1, h - 4), fill=navy)
    elif kind == "new":
        draw.rounded_rectangle((1, 1, w - 2, h - 2), radius=4, fill=green)
        draw.text((w / 2, h / 2), "N", fill="white", anchor="mm", font=font(FONT_BOLD, max(8, h // 2)))
    elif kind == "panel":
        draw.rounded_rectangle((1, 1, w - 2, h - 2), radius=3, fill=navy)
        draw.line((6, h // 2 - 3, w - 6, h // 2 - 3), fill="white", width=1)
        draw.line((6, h // 2 + 2, w - 6, h // 2 + 2), fill="white", width=1)
    elif kind == "mail":
        draw.rounded_rectangle((2, 4, w - 2, h - 4), radius=2, outline=navy, width=2)
        draw.line((3, 5, w // 2, h // 2 + 1), fill=navy, width=2)
        draw.line((w - 3, 5, w // 2, h // 2 + 1), fill=navy, width=2)
    elif kind == "pm":
        draw.rounded_rectangle((2, 3, w - 2, h - 5), radius=3, fill=navy)
        draw.polygon(((7, h - 5), (7, h - 1), (12, h - 5)), fill=navy)
        draw.line((6, 8, w - 6, 8), fill="white", width=1)
    elif kind == "arrow_up":
        draw.line((w // 2, h - 4, w // 2, 5), fill=navy, width=2)
        draw.polygon(((w // 2, 2), (5, 9), (w - 5, 9)), fill=navy)
    elif kind == "arrow_down":
        draw.line((w // 2, 4, w // 2, h - 5), fill=navy, width=2)
        draw.polygon(((w // 2, h - 2), (5, h - 9), (w - 5, h - 9)), fill=navy)
    elif kind == "info":
        draw.ellipse((1, 1, w - 2, h - 2), fill=navy)
        draw.text((w / 2, h / 2 + 1), "i", fill="white", anchor="mm", font=font(FONT_BOLD, max(10, h - 7)))
    elif kind == "key":
        draw.ellipse((2, 4, 11, 13), outline=(255, 255, 255, 255), width=2)
        draw.line((9, 11, w - 3, h - 3), fill="white", width=2)
        draw.line((w - 7, h - 7, w - 4, h - 10), fill="white", width=2)
    elif kind == "avatar":
        draw.ellipse((2, 2, w - 2, h - 2), fill=(214, 225, 237, 255), outline=navy, width=1)
        draw.ellipse((w // 2 - 5, 6, w // 2 + 5, 16), fill=navy)
        draw.arc((6, 10, w - 6, h + 8), start=200, end=340, fill=navy, width=2)
    elif kind == "read":
        draw.rounded_rectangle((2, 5, w - 2, h - 4), radius=2, fill=(205, 220, 235, 255), outline=navy)
        draw.line((4, 7, w // 2, h // 2 + 1), fill=navy, width=1)
        draw.line((w - 4, 7, w // 2, h // 2 + 1), fill=navy, width=1)
    else:
        draw.ellipse((2, 2, w - 2, h - 2), fill=navy)
    target.mkdir(parents=True, exist_ok=True)
    suffix = Path(name).suffix.lower()
    if suffix == ".gif":
        image.convert("P", palette=Image.Palette.ADAPTIVE).save(target / name, optimize=True)
    else:
        image.save(target / name, optimize=True)

# Branded background images retained for old CSS references.
save_gradient("gradient_bg.png", (8, 18), (12, 23, 37), (29, 54, 82))
save_gradient("branding_bg.png", (8, 120), (0, 78, 152), (27, 120, 191))
save_gradient("gradient_bp.png", (8, 22), (28, 54, 83), (57, 91, 126))
save_gradient("primarynav_bg.png", (8, 32), (25, 43, 64), (43, 74, 106), horizontal=True)
save_gradient("button.png", (8, 32), (70, 113, 146), (25, 54, 82))
save_gradient("fb_gradient.png", (8, 40), (235, 242, 248), (206, 220, 235))
save_gradient("tile_back.gif", (32, 32), (0, 50, 106), (0, 78, 152))
(IMAGES / "tile_back.gif").unlink(missing_ok=True)
(PIC / "tile_back.gif").parent.mkdir(parents=True, exist_ok=True)
# The legacy 2.css path expects this file under pic/.
gradient((32, 32), (0, 50, 106), (0, 78, 152)).save(PIC / "tile_back.gif", optimize=True)

logo = gradient((460, 120), (10, 43, 77), (0, 78, 152), horizontal=True)
draw = ImageDraw.Draw(logo)
draw.ellipse((25, 31, 91, 97), fill=(82, 143, 108), outline=(223, 242, 231), width=2)
draw.polygon(((58, 43), (58, 85), (82, 64)), fill="white")
draw.text((110, 35), "BitTorrent Work", fill="white", font=font(FONT_BOLD, 30))
draw.text((112, 75), "laboratório seguro", fill=(208, 225, 241), font=font(FONT_REGULAR, 15))
logo.save(IMAGES / "logo.jpg", quality=92, optimize=True)

save_icon("key.png", "key", (18, 18), IMAGES)
save_icon("default_thumb.png", "avatar", (50, 50), IMAGES)
save_icon("info.png", "info", (28, 28), IMAGES)
save_icon("aff_tick.gif", "tick", (14, 14), PIC)
save_icon("aff_cross.gif", "cross", (14, 14), PIC)
save_icon("warnedbig.gif", "warning", (16, 16), PIC)
save_icon("warned.gif", "warning", (14, 14), PIC)
save_icon("warned0.gif", "info", (14, 14), PIC)
save_icon("star.gif", "star", (14, 14), PIC)
save_icon("new.png", "new", (24, 14), PIC)
save_icon("updated.png", "new", (24, 14), PIC)
save_icon("panel_on.gif", "panel", (20, 16), PIC)
save_icon("readpm.gif", "read", (18, 16), PIC)
save_icon("unreadpm.gif", "pm", (18, 16), PIC)
save_icon("arrowup.gif", "arrow_up", (14, 14), PIC)
save_icon("arrowdown.gif", "arrow_down", (14, 14), PIC)
save_icon("logo.gif", "new", (80, 24), PIC)
save_icon("multipage.gif", "panel", (14, 14), PIC)
save_icon("tbani22.gif", "panel", (14, 14), PIC)
save_icon("tbdev_btn_red.png", "new", (120, 28), PIC)
save_icon("uk.gif", "new", (18, 12), PIC / "flag")
save_icon("user_green.png", "avatar", (16, 16), IMAGES)
save_icon("user_off.png", "cross", (16, 16), IMAGES)
save_icon("users.png", "avatar", (20, 20), PIC / "staff")
save_icon("mail.png", "mail", (20, 20), PIC / "staff")
save_icon("reputation_pos.gif", "tick", (12, 12), PIC / "rep")
save_icon("reputation_highpos.gif", "tick", (12, 12), PIC / "rep")
save_icon("reputation_neg.gif", "cross", (12, 12), PIC / "rep")
save_icon("reputation_highneg.gif", "cross", (12, 12), PIC / "rep")
save_icon("reputation_balance.gif", "panel", (12, 12), PIC / "rep")
for rating in range(1, 6):
    save_icon(f"{rating}.gif", "star", (18, 18), PIC)
save_icon("default_avatar.gif", "avatar", (50, 50), PIC / "forumicons")
save_icon("cat_test.gif", "new", (42, 42), PIC / "caticons")
save_icon("cat_software.gif", "panel", (42, 42), PIC / "caticons")
save_icon("cat_docs.gif", "read", (42, 42), PIC / "caticons")
emoticon_source = (ROOT / 'include' / 'emoticons.php').read_text(encoding='utf-8')
for smiley in sorted(set(re.findall(r'=>\s*[\'\"]([^\'\"]+\.gif)', emoticon_source))):
    save_icon(smiley, 'panel', (18, 18), PIC / 'smilies')

# Prevent opendir() warnings in administrative screens even when no custom icons exist.
for directory in (PIC / "caticons", PIC / "staff", PIC / "rep", PIC / "smilies", PIC / "forumicons"):
    (directory / ".gitkeep").touch()
