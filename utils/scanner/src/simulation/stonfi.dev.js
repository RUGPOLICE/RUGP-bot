import { SendMode, toNano, } from '@ton/core';
import { allTxsOk, createTransferBody, calculateLoss, getJettonBalance, getJettonWallet } from './utils.js';
import { DEX, pTON } from '@ston-fi/sdk';


export async function simulateStonfiDev(client, chain, master, simulator, jettonWallet, buyAmount) {

    try {

        const router = chain.openContract(new DEX.v1.Router());
        // const pool = chain.openContract(await router.getPoolAddress({
        //     token0: master,
        //     token1: 'EQAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAM9c',
        // }));

        // console.log(await pool.getExpectedOutputs({
        //     amount: buyAmount,
        //     jettonWallet: jettonWallet,
        // }))

        const txArgs = {
            offerAmount: toNano("1"),
            askJettonAddress: master,
            minAskAmount: toNano("0.1"),
            proxyTon: new pTON.v1(),
            userWalletAddress: simulator.address,
        };

        await router.sendSwapTonToJetton(simulator.getSender(), txArgs);
        const actualBalance = await getJettonBalance(chain, jettonWallet);
        // console.log(actualBalance);

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

function getActualPayout(transactions, pool) {
    for (const tx of transactions) {
        if (tx.description.type !== "generic")
            continue;
        for (const child of tx.outMessages.values()) {
            if (child.info.type !== "external-out" || !child.info.src.equals(pool))
                continue;
            const body = child.body.beginParse();
            const op = body.loadUint(32);
            if (op !== 0x9c610de3)
                continue;
            skipAsset(body); // asset in
            skipAsset(body); // asset out
            body.loadCoins(); // amount in
            return body.loadCoins() // amount out
        }
    }
    return 0n;
}

function skipAsset(cs) {
    const type = cs.loadUint(4);
    if (type === 0b0001)
        cs.skip(264);
    else if (type === 0b0010)
        cs.skip(32);
}
