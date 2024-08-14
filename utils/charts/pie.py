import matplotlib.pyplot as plt
import sys

values = list(map(lambda x: float(x), sys.argv[2:]))
fig, ax = plt.subplots()
explode = [0.01 * (len(values) - i) for i in range(len(values))]
ax.pie(values, explode=explode, labeldistance=1.2, labels=list(map(lambda x: f'{x}%', sys.argv[2:])))
plt.savefig(sys.argv[1], transparent=True, format='png')
