import { Address, Cell } from "@ton/core";

export interface JettonInfo {
    address: Address;
    name: string;
    symbol: string;
    decimals: number;
    admin: Address | null;
    supply: bigint;
    walletCode: Cell;
}
