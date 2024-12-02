import { beginCell, BitReader, Cell, CellType, Dictionary, toNano } from '@ton/core';
import { Blockchain, RemoteBlockchainStorage, wrapTonClient4ForRemote } from '@ton/sandbox';
import { request, gql } from 'graphql-request';
import { libs } from './libs.js';
import { getJettonWallet } from './utils.js';
import { simulateDedust } from './dedust.js';
import { simulateStonfi } from './stonfi.js';
import { simulateStonfiV2 } from './stonfi-v2.js';


const BUY_AMOUNT = toNano(5);
const DTON_ENDPOINT = 'https://dton.io/graphql/';


export async function simulateActions(dex, client, clientV4, master, walletCode) {

    const chain = await Blockchain.create({
        storage: new RemoteBlockchainStorage(wrapTonClient4ForRemote(clientV4))
    });

    // new jetton wallets have library code and we have to load libraries from realchain ourselfs
    // https://github.com/ton-org/sandbox#sandbox-pitfalls

    const libsDict = chain.libs
        ? chain.libs.beginParse().loadDictDirect(Dictionary.Keys.Buffer(32), Dictionary.Values.Cell())
        : Dictionary.empty(Dictionary.Keys.Buffer(32), Dictionary.Values.Cell());

    if (walletCode.isExotic && walletCode.type === CellType.Library) {

        const br = new BitReader(walletCode.bits);
        br.skip(8);

        const libHash = br.loadBuffer(32);
        const lib = await getLibrary(libHash);
        libsDict.set(libHash, lib);

    }

    if (dex.includes('stonfi-v2'))
        libs.forEach(lib => libsDict.set(lib.hash, lib.code));
    chain.libs = beginCell().storeDictDirect(libsDict).endCell();

    const simulator = await chain.treasury('simulator');
    const jettonWallet = await getJettonWallet(chain, simulator.address, master);

    return {
        dedust: dex.includes('dedust') ? await simulateDedust(chain, master, simulator, jettonWallet, BUY_AMOUNT) : null,
        stonfi: dex.includes('stonfi') ? await simulateStonfi(client, chain, master, simulator, jettonWallet, BUY_AMOUNT) : null,
        'stonfi-v2': dex.includes('stonfi-v2') ? await simulateStonfiV2(client, chain, master, simulator, jettonWallet, BUY_AMOUNT) : null,
    };
}

async function getLibrary(hash) {
    const query = gql`
        query {
            get_lib(lib_hash: "${hash.toString("hex").toUpperCase()}")
        }
    `;

    const data = await request(DTON_ENDPOINT, query);
    return Cell.fromBase64(data['get_lib']);
}
