import { StonApiClient } from '@ston-fi/api';

export async function simulateStonfi(master) {

    const client = new StonApiClient();

    try {

        const asset = await client.getAsset(master.toString());
        return {
            deprecated: asset.deprecated,
            taxable: asset.taxable,
        };

    } catch (error) {
        return null;
    }
}
