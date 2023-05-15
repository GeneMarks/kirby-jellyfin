const jellyfinTrigger = document.querySelector('#jellyfin-trigger')

// Attach an event listener to the jellyfinTrigger button. Fetches Jellyfin content when clicked,
// only if the canFetchJellyContent flag is true
jellyfinTrigger.addEventListener('click', async function() {
    if (canFetchJellyContent) { fetchJellyContent() }
})

let canFetchJellyContent = true

// Fetches Jellyfin content from the server and renders it on the page
function fetchJellyContent() {

    const jellyfinContent = document.querySelector('#jellyfin-content')
    let jellyfinContentLoading = jellyfinContent.querySelector('#jelly-loading')

    // Don't attempt to fetch if the loading div is gone from a prior successful fetch
    if (!jellyfinContentLoading) return

    // Disable flag while fetching to avoid spamming
    canFetchJellyContent = false
    fetch(`${window.location.origin}/jellyfin-watched`)
        .then(response => response.text())
        .then(data => {
            // Parse the received JSON data into items and render them on the page
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
                    <div><img src="${window.location.origin}/assets/images/jellyfin/${item.LastPlayedDate.replace(/:|\./g, '')}.webp" onerror="this.style.display='none'"/></div>
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
        // Delete the loading div if fetch is successful
        .then(() => jellyfinContentLoading.remove())
        .catch(error => console.error(error))
        // After attempting fetch, enable flag again
        .finally(() => canFetchJellyContent = true)

}