import sys
import requests
import mplfinance as mpf
import pandas as pd

network = sys.argv[2]
pool = sys.argv[3]
frame = sys.argv[4]
aggregate = sys.argv[5]

data = requests.get(
    f'https://api.geckoterminal.com/api/v2/networks/{network}/pools/{pool}/ohlcv/{frame}',
    params={'aggregate': aggregate, 'limit': 50},
    headers={'Accept': 'application/json;version=20230302'}
).json()['data']['attributes']['ohlcv_list']

ohlcv = pd.DataFrame(data, columns=['Unix', 'Open', 'High', 'Low', 'Close', 'Volume'])
ohlcv['Date'] = pd.to_datetime(ohlcv['Unix'], unit='s', origin='unix')
ohlcv.set_index('Date', inplace=True)

mpf.plot(
    ohlcv.iloc[::-1],
    type='candle',
    volume=True,
    style='yahoo',
    savefig=sys.argv[1],
    figsize=(12, 8),
    tight_layout=True,
)
