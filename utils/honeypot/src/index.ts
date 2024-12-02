import { Address, toNano } from "@ton/core";
import { TonClient, TonClient4 } from "@ton/ton";
import { getHttpEndpoint, getHttpV4Endpoint } from "@orbs-network/ton-access";
import { Blockchain, RemoteBlockchainStorage, wrapTonClient4ForRemote } from "@ton/sandbox";
import { JettonInfo } from "./types";
import { DedustPoolFinder, StonfiPoolFinder, Dex, PoolFinder, PoolInfo } from "./dex";
import { Simulation, DedustSimulation, StonfiV1Simulation, StonfiV2Simulation } from "./simulation";
import { isKnownWallet } from "./known-contracts";
import { getJettonInfo } from "./utils";

async function index() {

    const client = new TonClient({
        endpoint: await getHttpEndpoint({ network: "mainnet" })
    });

    const clientV4 = new TonClient4({
        endpoint: await getHttpV4Endpoint({ network: "mainnet" })
    });

    const seqno = (await clientV4.getLastBlock()).last.seqno;
    const chain = await Blockchain.create({
        storage: new RemoteBlockchainStorage(wrapTonClient4ForRemote(clientV4), seqno)
    });

    const poolFinders: PoolFinder[] = [
        DedustPoolFinder.create(client),
        StonfiPoolFinder.create(client),
    ];

    const queryAddress = process.argv[2];
    if (Address.isFriendly(queryAddress) || Address.isRaw(queryAddress)) {

        const address = Address.parse(queryAddress);
        return await checkAddress(address, client, chain, poolFinders);

    } else return { success: false, message: 'Invalid address' };

}

function getSimulation(chain: Blockchain, dex: Dex, master: Address, pool: Address, amount: bigint): Promise<Simulation> {
    switch (dex) {
        case Dex.DEDUST: return DedustSimulation.create(chain, master, pool, amount);
        case Dex.STONFI_V1: return StonfiV1Simulation.create(chain, master, pool, amount);
        case Dex.STONFI_V2: return StonfiV2Simulation.create(chain, master, pool, amount);
    }
}

async function checkAddress(address: Address, client: TonClient, chain: Blockchain, poolFinders: PoolFinder[] = []) {
    try {

        let masterInfo: JettonInfo = await getJettonInfo(client, address);
        const poolTasks = await Promise.all(poolFinders.map(x => x.findPools(address)));

        let pools: PoolInfo[];
        pools = poolTasks.flat().filter(x => x !== null).sort((a, b) => b.reservesUsd - a.reservesUsd);
        if (pools.length === 0) return { success: false, message: 'No pools found' };

        return await checkHoneypot(chain, masterInfo, pools[0]);

    } catch (e: any) {

        return { success: false, message: e.message, stack: e.stack };

    }
}

async function checkHoneypot(chain: Blockchain, jetton: JettonInfo, pool: PoolInfo) {
    const simulation = await getSimulation(chain, pool.dex, jetton.address, pool.address, toNano(30));
    const knownWallet = isKnownWallet(jetton.walletCode.hash());
    const simResult = await simulation.simulate();

    return {
        success: true,
        isKnownWallet: knownWallet,
        buy: simResult.buy?.loss ?? -1,
        transfer: simResult.transfer?.loss ?? -1,
        sell: simResult.sell?.loss ?? -1,
    };
}

index().then(result => console.log(JSON.stringify(result)));
