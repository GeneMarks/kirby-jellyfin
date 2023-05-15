const jellyfinTrigger = document.querySelector('[data-jellypost]')

jellyfinTrigger.addEventListener('click', async function() {
    if (canfetchJellyContent) { fetchJellyContent(jellyfinTrigger) }
})

let canfetchJellyContent = true

function fetchJellyContent(jellyfinTrigger) {
    const jellyfinContent = document.querySelector('#profile-content-2')
    let jellyfinContentLoading = jellyfinContent.querySelector('#jelly-loading')
    if (!jellyfinContentLoading) return

    canfetchJellyContent = false
    fetch(jellyfinTrigger.getAttribute('data-jellypost'))
        .then(response => response.text())
        .then(data => {
            const items = JSON.parse(data)

            let ul = document.createElement('ul')
            ul.classList.add('jellyfin')

            for (const item of items) {
                const date = new Date(item.LastPlayedDate)
                const formattedDate = date.toLocaleString('en-US', {
                    year: 'numeric',
                    month: '2-digit',
                    day: '2-digit',
                })

                const li = document.createElement('li')
                li.classList.add('jellyfin__item')

                li.innerHTML = `
                    <div><img src="${global.siteURL}/assets/images/jellyfin/${item.LastPlayedDate.replace(/:|\./g, '')}.webp" onerror="this.style.display='none'"/></div>
                    <div>
                        <h3>${item.Name}</h3>
                        <div class="jellyfin__details">
                            <p>${item.SeriesName}</p>
                            <p>Watched on ${formattedDate}</p>
                            <p>${item.PlayCount} Play${item.PlayCount > 1 ? 's' : ''}</p>
                        </div>
                    </div>
                `
                ul.appendChild(li)
            }

            jellyfinContent.appendChild(ul)
        })
        .then(() => jellyfinContentLoading.remove())
        .catch(error => console.error(error))
        .finally(() => canfetchJellyContent = true)
}