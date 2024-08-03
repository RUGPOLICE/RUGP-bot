import { Dictionary } from '@ton/core';
import { sha256 } from '@ton/crypto';


const ONCHAIN_FLAG = 0x00;
const OFFCHAIN_FLAG = 0x01;

const masters = [
    Buffer.from("mg+Y3W+/Il7vgWXk5kQX7pMffuoABlNDnntdzcBkTNY=", "base64"), // standard jetton minter v1
    Buffer.from("+D0FSQr3ycxYAZSIx7JTyUktScJdEtCTg+UugVN+NDo=", "base64"), // standard jetton minter v2
    Buffer.from("GNW254D/C7RRJUwsdg0J1uSFY4zRQHq7lweHUsPBye4=", "base64"), // governed jetton minter (USDT)
    Buffer.from("BXGXbGPsG3VQIwomCdvts24bZO+NAioWs06lcGMYWy8=", "base64"), // jetton minter discoverable
];

const wallets = [
    Buffer.from("vrBoPr64kn/p/I7AoYvH3ReJlomCWhIeq0bFo6hg0M4=", "base64"), // standard jetton wallet v1
    Buffer.from("jSjqQht36AX+pSrPM1KWSZ8Drsjp/SHdtfJWSqZcSN4=", "base64"), // standard jetton wallet v2
    Buffer.from("iUaPAseOVwgC45l5yFFvw43wfqdqSDV+BTbyuns+43s=", "base64"), // governed jetton wallet (USDT)
    Buffer.from("p2DWKdU0PnbQRQF9ncIW/IoweoN3gV/rKwpcSQ5zNIY=", "base64"), // jetton wallet from discoverable
];


export function isKnownMaster(hash) {
    return masters.some(master => master.equals(hash));
}

export function isKnownWallet(hash) {
    return wallets.some(wallet => wallet.equals(hash));
}


export async function getJettonData(client, address) {

    const stack = (await client.runMethod(address, 'get_jetton_data')).stack;
    const supply = stack.readBigNumber();
    stack.skip(1);
    const admin = stack.readAddressOpt();
    const contentCell = stack.readCell();
    const jettonWalletCode = stack.readCell();

    let parsedContent;
    try {
        parsedContent = await parseContent(contentCell);
    } catch {
        // console.log('Cannot parse jetton metadata (probably url are unavailable). Relaying on ton api...');
        const response = await fetch(`https://tonapi.io/v2/jettons/${address}`);
        const json = await response.json();
        const meta = json['metadata'];
        parsedContent = {
            name: meta['name'],
            symbol: meta['symbol'],
            decimals: meta['decimals']
        };
    }

    return {
        ...parsedContent,
        supply: supply,
        admin: admin,
        walletCode: jettonWalletCode
    }
}

/*export interface JettonData {
    name: string;
    symbol: string;
    decimals: number;
    admin: Address | null;
    supply: bigint;
    walletCode: Cell;
}*/

async function parseContent(content) {
    const cs = content.beginParse();
    const layout = cs.loadUint(8);
    if (layout === OFFCHAIN_FLAG) {
        const url = cs.loadStringTail();
        const response = await fetch(url);
        return await response.json();
    } else if (layout === ONCHAIN_FLAG) {
        const dict = cs.loadDict(Dictionary.Keys.Buffer(32), Dictionary.Values.Cell());
        let name = dict.get(await sha256("name"))?.beginParse().loadStringTail();
        let symbol = dict.get(await sha256("symbol"))?.beginParse().loadStringTail();
        let decimals = dict.get(await sha256("decimals"))?.beginParse().loadStringTail();

        const uriCell = dict.get(await sha256("uri"));
        if (uriCell) {
            const slice = uriCell.beginParse();
            slice.skip(8); // skip snake prefix?
            const uri = slice.loadStringTail();
            const response = await fetch(uri);
            const json = await response.json();
            name ??= json["name"];
            symbol ??= json["symbol"];
            decimals ??= json["decimals"];
        }

        return {
            name: name ?? "NOT_FOUND",
            symbol: symbol ?? "NOT_FOUND",
            decimals: Number.isInteger(decimals) ? Number.parseInt(decimals) : 9
        }
    }
    throw new Error("Unknown layout format");
}

/*
interface JettonContent {
    name: string;
    symbol: string;
    decimals: number;
}*/
