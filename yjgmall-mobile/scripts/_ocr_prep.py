#!/usr/bin/env python3
from PIL import Image, ImageOps, ImageEnhance

im = Image.open("/www/wwwroot/g.aifeili.cn/public/tmp-user-shot.png")
print("size", im.size)
im = im.convert("L")
im = ImageOps.autocontrast(im)
im = im.resize((im.width * 2, im.height * 2), Image.LANCZOS)
im = ImageEnhance.Contrast(im).enhance(2.2)
im.save("/tmp/shot-ocr.png")
w, h = im.size
im.crop((0, 0, w, h // 3)).save("/tmp/shot-top.png")
im.crop((0, h // 3, w, 2 * h // 3)).save("/tmp/shot-mid.png")
im.crop((0, 2 * h // 3, w, h)).save("/tmp/shot-bot.png")
print("saved parts")
