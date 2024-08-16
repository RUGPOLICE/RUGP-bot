import { Address, fromNano, SendMode, toNano, } from '@ton/core';
import { allTxsOk, createTransferBody, calculateLoss, getJettonBalance, getJettonWallet } from './utils.js';
import { DEX, pTON } from '@ston-fi/sdk';

const PTON_WALLET = 'EQARULUYsmJq1RiZ-YiH-IJLcAZUVkVff-KBPwEmmaQGH6aC';


export async function simulateStonfi(client, chain, master, simulator, jettonWallet, buyAmount) {

    try {

        const router = chain.openContract(new DEX.v1.Router());
        const pool = chain.openContract(await router.getPool({
            token0: master,
            token1: (new pTON.v1()).address,
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
                transfer: null,
                buy: null,
                sell: null
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
                transfer: null,
                buy: null,
                sell: null
            };

        const actualBalance = await getJettonBalance(chain, jettonWallet);
        const buyResult = {
            loss: calculateLoss(actualBalance, expectedBuy.jettonToReceive)
        };

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
                transfer: transferResult,
                buy: buyResult,
                sell: null
            };

        const actualTonPayout = getActualPayout(resultSell.transactions, pool.address, simulator.address, checkAnotherSwapWallet);
        const sellResult = {
            loss: calculateLoss(actualTonPayout, expectedSell.jettonToReceive)
        };

        return {
            pool: pool.address,
            transfer: transferResult,
            buy: buyResult,
            sell: sellResult
        };

    } catch (e) {

        return e.stack;

    }

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
