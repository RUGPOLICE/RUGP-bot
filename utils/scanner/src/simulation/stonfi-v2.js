import { Address, fromNano, SendMode, toNano, } from '@ton/core';
import { allTxsOk, createTransferBody, calculateLoss, getJettonBalance, getJettonWallet } from './utils.js';
import { DEX, pTON } from '@ston-fi/sdk';
import axios from 'axios';

const TON_ASSET = 'EQAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAM9c';
const PTON_WALLET = 'EQBiLHuQjDj4fNyCD7Ch5HwpNGldlb5g-LMwQ1kStQ4NM5kv';


export async function simulateStonfiV2(client, chain, master, simulator, jettonWallet, buyAmount) {

    try {

        const routerInfo = await getRouterInfo(master.toString());
        if (!routerInfo) return null;

        const [routerClass, pTon] = getRouter(routerInfo);
        const v1 = routerInfo.router.pton_version === '1.0';

        const router = chain.openContract(routerClass);
        const pool = chain.openContract(await router.getPool({
            token0: master,
            token1: pTon.address,
        }));

        const { token0WalletAddress, token1WalletAddress } = await pool.getPoolData();
        let [pTonWallet, jettonPoolWallet] = [token0WalletAddress, token1WalletAddress];
        let checkAnotherSwapWallet = false;

        if (token1WalletAddress.toString() === PTON_WALLET) {

            [pTonWallet, jettonPoolWallet] = [jettonPoolWallet, pTonWallet];
            checkAnotherSwapWallet = true;

        }

        const expectedBuy = await pool.getExpectedOutputs({
            amount: buyAmount,
            jettonWallet: pTonWallet,
        });

        if (expectedBuy.jettonToReceive === 0n) {
            return {
                pool: pool.address,
                buy: -1,
                sell: null,
                transfer: null,
            };
        }

        const txBuyArgs = {
            offerAmount: buyAmount,
            askJettonAddress: master,
            minAskAmount: toNano(0.1),
            proxyTon: new pTON.v1(),
            userWalletAddress: simulator.address,
        };

        const resultBuy = await router.sendSwapTonToJetton(simulator.getSender(), txBuyArgs);
        if (!allTxsOk(resultBuy.transactions))
            return {
                pool: pool.address,
                buy: null,
                sell: null,
                transfer: null,
            };

        const actualBalance = await getJettonBalance(chain, jettonWallet);
        const buyResult = calculateLoss(actualBalance, expectedBuy.jettonToReceive);

        // const transferResult = await simulateTransfer(chain, master, simulator, jettonWallet, actualBalance);
        const transferResult = null;

        const expectedSell = await pool.getExpectedOutputs({
            amount: actualBalance,
            jettonWallet: jettonPoolWallet,
        });

        const txSellArgs = {
            userWalletAddress: simulator.address,
            offerJettonAddress: master,
            offerAmount: actualBalance,
            proxyTon: new pTON.v1(),
            minAskAmount: 1,
            queryId: 1,
        };

        const resultSell = await router.sendSwapJettonToTon(simulator.getSender(), txSellArgs);
        if (!allTxsOk(resultSell.transactions))
            return {
                pool: pool.address,
                buy: buyResult,
                sell: null,
                transfer: transferResult,
            };

        const actualTonPayout = getActualPayout(resultSell.transactions, pool.address, simulator.address, checkAnotherSwapWallet);
        const sellResult = calculateLoss(actualTonPayout, expectedSell.jettonToReceive);

        return {
            pool: pool.address,
            buy: buyResult,
            sell: sellResult,
            transfer: transferResult,
        };

    } catch (e) {

        return e.stack;

    }

}

async function getRouterInfo(asset) {
    const response = await axios.post(`https://api.ston.fi/v1/pool/query?unconditional_asset=${asset}&dex_v2=true`);
    const poolData = findPoolByTokens(response.data.pool_list, asset, TON_ASSET);
    return poolData ? (await axios.get(`https://api.ston.fi/v1/routers/${poolData.router_address}`)).data : false;
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

async function simulateTransfer(chain, master, simulator, jettonWallet, amount) {

    const snapshot = chain.snapshot();
    const another = await chain.treasury('another');
    const result = await simulator.send({
        to: jettonWallet,
        value: toNano(0.06),
        sendMode: SendMode.PAY_GAS_SEPARATELY,
        body: createTransferBody(amount, another.address, simulator.address, 1n, null)
    });

    // console.log("TRANSFER TABLE");
    // printTransactionFees(result.transactions);

    if (!allTxsOk(result.transactions))
        return null;;

    const anotherJettonWallet = await getJettonWallet(chain, another.address, master);
    const state = (await chain.getContract(anotherJettonWallet)).accountState;
    const balance = state?.type === "active" ? await getJettonBalance(chain, anotherJettonWallet) : 0n;
    const loss = calculateLoss(balance, amount);

    // console.log("Actual transferred:", balance);
    // console.log("Expected transferred", amount);
    // console.log("Transfer diff:", loss);

    await chain.loadFrom(snapshot);
    return {
        loss: loss
    };
}

function getActualPayout(transactions, pool, owner, checkAnotherSwapWallet) {
    for (const tx of transactions) {

        if (tx.description.type !== "generic")
            continue;

        for (const child of tx.outMessages.values()) {
            if (child.info.type !== "internal" || !child.info.src.equals(pool))
                continue;

            const body = child.body.beginParse();
            const op = body.loadUint(32);

            if (op !== 0xf93bb43f)
                continue;

            body.loadUint(64); // query id
            const ownerAddress = body.loadAddress(); // owner
            body.loadUint(32); // exit code

            if (!ownerAddress.equals(owner))
                continue;

            let out;
            const cell = body.loadRef().beginParse();

            if (!checkAnotherSwapWallet) out = cell.loadCoins();
            else cell.loadCoins();
            cell.loadAddress();

            if (checkAnotherSwapWallet) out = cell.loadCoins();
            else cell.loadCoins();
            cell.loadAddress();

            return out;
        }
    }
    return 0n;
}
