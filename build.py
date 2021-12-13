#!/usr/bin/python3

import subprocess
import argparse
import sys

parser = argparse.ArgumentParser(description='Storge builder')
parser.add_argument('filename')

args = parser.parse_args()


steps = [
    ('mv  ./back/.env .env.tmp', '.'),
    (f'zip -r ../{args.filename} .', './back'),
    ('mv .env.tmp ./back/.env', '.')
]

for (step, cwd) in steps:
    subprocess.run(step, cwd=cwd, shell=True)
