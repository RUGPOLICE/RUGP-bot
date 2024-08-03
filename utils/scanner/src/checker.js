import { Cell, TonClient, TonClient4 } from '@ton/ton';
import { getHttpEndpoint, getHttpV4Endpoint } from '@orbs-network/ton-access';
import { isKnownMaster, isKnownWallet, getJettonData } from './utils.js';
import { simulateActions } from './simulation/simulation.js';


export async function checkForHoneypot(address, dex) {

    const client = new TonClient({ endpoint: await getHttpEndpoint({ network: 'mainnet' }) });
    const clientV4 = new TonClient4({ endpoint: await getHttpV4Endpoint({ network: 'mainnet' }) });

    const state = await client.getContractState(address);
    if (state.code === null)
        return {
            success: false,
            message: 'Cannot parse code',
        };

    const codeCell = Cell.fromBoc(state.code)[0];
    const data = await getJettonData(client, address);

    const knownMaster = isKnownMaster(codeCell.hash());
    const knownWallet = isKnownWallet(data.walletCode.hash());

    const simulation = await simulateActions(dex, client, clientV4, address, data.walletCode);
    return {
        success: true,
        name: data.name,
        symbol: data.symbol,
        isKnownMaster: knownMaster,
        isKnownWallet: knownWallet,
        dedust: {
            pool: simulation.dedust?.pool.toString(),
            taxBuy: simulation.dedust?.buy?.loss,
            taxSell: simulation.dedust?.sell?.loss,
            taxTransfer: simulation.dedust?.transfer?.loss,
        },
        stonfi: {
            pool: null,
            deprecated: null,
            taxable: null,
        },
        sfd: simulation.sfd,
    };

}
