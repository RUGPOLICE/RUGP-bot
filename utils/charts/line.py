import matplotlib.pyplot as plt
import matplotlib.dates as dts
import matplotlib.ticker as ticker
import numpy as np
import sys
from datetime import datetime
from matplotlib.axes import Axes
import re


def formatter(x, pos):
    if not x:
        return "0"
    if x < 1e-2:
        mat = ['₀', '₁', '₂', '₃', '₄', '₅', '₆', '₇', '₈', '₉', '₁₀', '₁₁', '₁₂']
        return re.sub(r'(.*\.0)(0+)(\d*)', lambda m: m.group(1) + mat[len(m.group(2))] + m.group(3)[:2], f'{x:f}').rstrip('0')
    if x < 1000:
        return f'{x:.2f}'
    if x < 1e6:
        return str(round(x / 1e3)) + "K"
    if x < 1e9:
        return str(round(x / 1e6)) + "M"
    else:
        return str(round(x / 1e9)) + "B"


pools = []
for pool in sys.argv[2:]:
    name, dates, prices = pool.split(':')
    pools.append({
        'name': name,
        'date': list(map(lambda x: datetime.strptime(x, '%d.%m.%Y'), dates.split(','))),
        'price': list(map(float, prices.split(','))),
    })

fig, axs = plt.subplots(len(pools), 1, sharex=True, sharey=True)
if isinstance(axs, Axes):
    axs = [axs]

for i, ax in enumerate(axs):
    ax.plot(pools[i]['date'], pools[i]['price'], lw=1)
    ax.grid(True)
    ax.set_ylabel(pools[i]['name'])

    ax.xaxis.set_major_locator(dts.DayLocator(bymonthday=range(1, 31, 3)))
    ax.xaxis.set_major_formatter(dts.DateFormatter('%d'))

    ax.yaxis.set_major_locator(ticker.MaxNLocator(10))
    ax.yaxis.set_major_formatter(formatter)

    ax.tick_params(axis='x', which='major')
    for label in ax.get_xticklabels(which='major'):
        label.set_horizontalalignment('center')

plt.savefig(sys.argv[1], transparent=True, format='png')
