import { Address, internal, toNano, TupleBuilder } from '@ton/core';
import { mnemonicToPrivateKey } from '@ton/crypto';
import { TonClient, WalletContractV4 } from '@ton/ton';
import { DEX, pTON } from '@ston-fi/sdk';
import axios from 'axios';

const TONCENTER_API_KEY = '555cb94a22063cfb33d929398afac6210570d543e58d56a49665695c5ce4da20';
const WALLET = 'UQDrnS0RQrCEtxJaZwyQNwZY5Q1VsrX92DE2bivmufwE9E-w';
const TON_ASSET = 'EQAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAM9c';
const BUY_AMOUNT = 5;

const address = process.argv[2];
checkJetton(address).catch(e => response({ success: false, message: e.message, stack: e.stack }));

async function checkJetton(address) {
    if (Address.isFriendly(address) || Address.isRaw(address)) response(await simulate(Address.parse(address)));
    else response({ success: false, message: 'Invalid address' });
}

async function simulate(asset) {

    try {

        const routerInfo = await getRouterInfo(asset.toString());
        if (!routerInfo) return null;

        const [routerClass, pTon] = getRouter(routerInfo);
        const v1 = routerInfo.router.pton_version === '1.0';

        const mnemonics = [
            'sentence',
            'gift',
            'pyramid',
            'erupt',
            'review',
            'shield',
            'random',
            'worry',
            'usage',
            'alcohol',
            'sight',
            'spoon',
            'already',
            'ski',
            'fortune',
            'hidden',
            'settle',
            'cereal',
            'walnut',
            'volume',
            'loop',
            'defy',
            'ketchup',
            'ladder',
        ];
        const keyPair = await mnemonicToPrivateKey(mnemonics);

        const workchain = 0;
        const wallet = WalletContractV4.create({
            workchain,
            publicKey: keyPair.publicKey,
        });

        const client = new TonClient({ endpoint: 'https://toncenter.com/api/v2/jsonRPC', apiKey: TONCENTER_API_KEY });
        const router = client.open(routerClass);
        const contract = client.open(wallet);

        if (!v1) {

            const txArgs = {
                userWalletAddress: contract.address.toString(),
                proxyTon: pTon,
                offerAmount: BigInt(toNano(BUY_AMOUNT)),
                askJettonAddress: asset.toString(),
                minAskAmount: 0,
            };

            console.log(internal(await router.getSwapTonToJettonTxParams(txArgs)).body.toBoc().toString('hex'));
            console.log((await router.getSwapTonToJettonTxParams(txArgs)).body.toBoc().toString('hex'));

        }

    } catch (e) {

        return e.stack;

    }

}

async function getRouterInfo(asset) {
    const response = await axios.post(`https://api.ston.fi/v1/pool/query?unconditional_asset=${asset}&dex_v2=true`);
    const poolData = findPoolByTokens(response.data.pool_list, asset, TON_ASSET);
    return poolData ? (await axios.get(`https://api.ston.fi/v1/routers/${poolData.router_address}`)).data : false;
}

async function getOfferWallet(jetton, router) {
    const response = await axios.get(`https://api.ston.fi/v1/jetton/${jetton}/address?owner_address=${router}`);
    return response.data.address;
}

async function getJettonWallet(chain, owner, master) {
    const tb = new TupleBuilder();
    tb.writeAddress(owner);
    const result = await chain.runGetMethod(master, 'get_wallet_address', tb.build());
    return result.stackReader.readAddress();
}

function findPoolByTokens(poolList, token0Address, token1Address) {
    return poolList.find(pool =>
        (pool.token0_address === token0Address && pool.token1_address === token1Address) ||
        (pool.token0_address === token1Address && pool.token1_address === token0Address)
    ) || false;
}

function getRouter(routerInfo) {

    let router;
    let pTon;

    if (routerInfo.router.pton_version === '1.0') {

        router = new DEX.v1.Router();
        pTon = new pTON.v1();

    } else if (routerInfo.router.pton_version === '2.1') {

        router = DEX.v2_1.Router.create(routerInfo.router.address);
        pTon = pTON.v2_1.create(routerInfo.router.pton_master_address);

    } else {

        router = DEX.v2_2.Router.create(routerInfo.router.address);
        pTon = pTON.v2_1.create(routerInfo.router.pton_master_address);

    }

    return [router, pTon];

}

function response(data) {
    console.log(JSON.stringify(
        data,
        (key, value) => typeof value === 'bigint' ? value.toString() : value
    ));
}
