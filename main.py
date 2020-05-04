#!/usr/bin/env python3
# -*- coding: utf-8 -*-

import asyncio
from sanic import Sanic, response
from databases import Database

app = Sanic('1k')
db = Database('sqlite:///1k.db')


@app.route('/', methods=['GET', 'HEAD'])
async def welcome(request):
    return response.text("Welcome to the1024.club")


async def init():
    print("Connecting to DB ...")
    await db.connect()
    print("Creating table ...")
    await db.execute("""CREATE TABLE IF NOT EXISTS the_goods (
                            id INTEGER PRIMARY KEY,
                            fingerprint TEXT NOT NULL UNIQUE,
                            pubkey TEXT NOT NULL UNIQUE,
                            kilo TEXT
                        )""")


if __name__ == "__main__":
    asyncio.run(init())
    app.run(host="127.0.0.1", port=42069)
