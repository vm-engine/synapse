{{-- <!-- Triggers  -->

Message Trigger
<button
class="whitespace-nowrap rounded-radius bg-primary px-4 py-2 text-center text-sm font-medium tracking-wide text-on-primary transition hover:opacity-75 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary active:opacity-100 active:outline-offset-0 disabled:cursor-not-allowed disabled:opacity-75 dark:bg-primary-dark dark:text-on-primary-dark dark:focus-visible:outline-primary-dark" type="button" x-on:click="$dispatch('notify', {
        variant: 'message',
        sender:{
            name:'Jack Ellis',
            avatar:'https://penguinui.s3.amazonaws.com/component-assets/avatar-2.webp'},
            message: 'Hey, can you review the PR I just submitted? Let me know if you spot any issues!'
        }
    )">Message</button>
Info Trigger
<button
class="whitespace-nowrap rounded-radius bg-info px-4 py-2 text-center text-sm font-medium tracking-wide text-on-info transition hover:opacity-75 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-info active:opacity-100 active:outline-offset-0 disabled:cursor-not-allowed disabled:opacity-75" type="button" x-on:click="$dispatch('notify', {
        variant: 'info',
        title: 'Update Available',
        message: 'A new version of the app is ready for you. Update now to enjoy the latest features!'
    })">Info</button>
Success Trigger
<button
class="whitespace-nowrap rounded-radius bg-success px-4 py-2 text-center text-sm font-medium tracking-wide text-on-success transition hover:opacity-75 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-success active:opacity-100 active:outline-offset-0 disabled:cursor-not-allowed disabled:opacity-75" type="button" x-on:click="$dispatch('notify', {
        variant: 'success',
        title: 'Success!',
        message: 'Your changes have been saved. Keep up the great work!'
    })">Success</button>
Danger Trigger
<button
class="whitespace-nowrap rounded-radius bg-danger px-4 py-2 text-center text-sm font-medium tracking-wide text-on-danger transition hover:opacity-75 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-danger active:opacity-100 active:outline-offset-0 disabled:cursor-not-allowed disabled:opacity-75" type="button" x-on:click="$dispatch('notify', {
        variant: 'danger',
        title: 'Oops!',
        message: 'Something went wrong. Please try again. If the problem persists, we’re here to help!'
    })">Danger</button>
Warning Trigger
<button
class="whitespace-nowrap rounded-radius bg-warning px-4 py-2 text-center text-sm font-medium tracking-wide text-on-warning transition hover:opacity-75 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-warning active:opacity-100 active:outline-offset-0 disabled:cursor-not-allowed disabled:opacity-75" type="button" x-on:click="$dispatch('notify', {
        variant: 'warning',
        title: 'Action Needed',
        message: 'Your storage is getting low. Consider upgrading your plan.'
    })">Warning</button> --}}

<!-- Notifications -->
<div
    wire:ignore
    x-data="toastrRoot({{ Js::from(
        collect(['success' => 'Success', 'info' => 'Information', 'danger' => 'Error', 'warning' => 'Warning'])->filter(fn($title, $variant) => session($variant))->map(fn($title, $variant) => ['variant' => $variant, 'title' => $title, 'message' => session($variant)])->values(),
    ) }})"
    @notify.window="handleNotifyEvent($event)"
    x-init="initFlashNotifications()"
>

    <div
        class="z-999 group pointer-events-none fixed inset-x-8 top-0 flex max-w-full flex-col gap-2 bg-transparent px-6 py-6 md:bottom-0 md:left-[unset] md:right-0 md:top-[unset] md:max-w-sm"
        x-on:mouseenter="$dispatch('pause-auto-dismiss')"
        x-on:mouseleave="$dispatch('resume-auto-dismiss')"
    >
        <template
            x-for="(notification, index) in notifications"
            x-bind:key="notification.id"
        >
            <!-- root div holds all of the notifications  -->
            <div>
                <!-- Info Notification  -->
                <template x-if="notification.variant === 'info'">
                    <div
                        class="rounded-radius border-info bg-surface text-on-surface dark:bg-surface-dark dark:text-on-surface-dark toastr-notification pointer-events-auto relative border"
                        role="alert"
                        x-data="toastrNotification(notification, displayDuration, removeNotification)"
                        x-cloak
                        x-show="isVisible"
                        x-on:pause-auto-dismiss.window="pause"
                        x-on:resume-auto-dismiss.window="resume"
                        x-transition:enter="transition duration-300 ease-out"
                        x-transition:enter-end="translate-y-0"
                        x-transition:enter-start="translate-y-8"
                        x-transition:leave="transition duration-300 ease-in"
                        x-transition:leave-end="-translate-x-24 opacity-0 md:translate-x-24"
                        x-transition:leave-start="translate-x-0 opacity-100"
                    >
                        <div
                            class="bg-info/10 rounded-radius flex w-full items-center gap-2.5 p-4 transition-all duration-300">

                            <!-- Icon -->
                            <div
                                class="bg-info/15 text-info rounded-full p-0.5"
                                aria-hidden="true"
                            >
                                <svg
                                    class="size-5"
                                    aria-hidden="true"
                                    xmlns="http://www.w3.org/2000/svg"
                                    viewBox="0 0 20 20"
                                    fill="currentColor"
                                >
                                    <path
                                        fill-rule="evenodd"
                                        d="M18 10a8 8 0 1 1-16 0 8 8 0 0 1 16 0Zm-7-4a1 1 0 1 1-2 0 1 1 0 0 1 2 0ZM9 9a.75.75 0 0 0 0 1.5h.253a.25.25 0 0 1 .244.304l-.459 2.066A1.75 1.75 0 0 0 10.747 15H11a.75.75 0 0 0 0-1.5h-.253a.25.25 0 0 1-.244-.304l.459-2.066A1.75 1.75 0 0 0 9.253 9H9Z"
                                        clip-rule="evenodd"
                                    />
                                </svg>
                            </div>

                            <!-- Title & Message -->
                            <div class="flex flex-col gap-2">
                                <h3
                                    class="text-info text-sm font-semibold"
                                    x-cloak
                                    x-show="notification.title"
                                    x-text="notification.title"
                                ></h3>
                                <p
                                    class="text-pretty text-sm"
                                    x-cloak
                                    x-show="notification.message"
                                    x-text="notification.message"
                                ></p>
                            </div>

                            <!--Dismiss Button -->
                            <button
                                class="ml-auto"
                                type="button"
                                aria-label="dismiss notification"
                                x-on:click="dismiss"
                            >
                                <svg
                                    class="size-5 shrink-0"
                                    aria-hidden="true"
                                    xmlns="http://www.w3.org/2000/svg"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                    fill="none"
                                    stroke-width="2"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M6 18L18 6M6 6l12 12"
                                    />
                                </svg>
                            </button>
                        </div>
                    </div>
                </template>

                <!-- Success Notification  -->
                <template x-if="notification.variant === 'success'">
                    <div
                        class="rounded-radius border-success bg-surface text-on-surface dark:bg-surface-dark dark:text-on-surface-dark toastr-notification pointer-events-auto relative border"
                        role="alert"
                        x-data="toastrNotification(notification, displayDuration, removeNotification)"
                        x-cloak
                        x-show="isVisible"
                        x-on:pause-auto-dismiss.window="pause"
                        x-on:resume-auto-dismiss.window="resume"
                        x-transition:enter="transition duration-300 ease-out"
                        x-transition:enter-end="translate-y-0"
                        x-transition:enter-start="translate-y-8"
                        x-transition:leave="transition duration-300 ease-in"
                        x-transition:leave-end="-translate-x-24 opacity-0 md:translate-x-24"
                        x-transition:leave-start="translate-x-0 opacity-100"
                    >
                        <div
                            class="bg-success/10 rounded-radius flex w-full items-center gap-2.5 p-4 transition-all duration-300">

                            <!-- Icon -->
                            <div
                                class="bg-success/15 text-success rounded-full p-0.5"
                                aria-hidden="true"
                            >
                                <svg
                                    class="size-5"
                                    aria-hidden="true"
                                    xmlns="http://www.w3.org/2000/svg"
                                    viewBox="0 0 20 20"
                                    fill="currentColor"
                                >
                                    <path
                                        fill-rule="evenodd"
                                        d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm3.857-9.809a.75.75 0 0 0-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 1 0-1.06 1.061l2.5 2.5a.75.75 0 0 0 1.137-.089l4-5.5Z"
                                        clip-rule="evenodd"
                                    />
                                </svg>
                            </div>

                            <!-- Title & Message -->
                            <div class="flex flex-col gap-2">
                                <h3
                                    class="text-success text-sm font-semibold"
                                    x-cloak
                                    x-show="notification.title"
                                    x-text="notification.title"
                                ></h3>
                                <p
                                    class="text-pretty text-sm"
                                    x-cloak
                                    x-show="notification.message"
                                    x-text="notification.message"
                                ></p>
                            </div>

                            <!--Dismiss Button -->
                            <button
                                class="ml-auto"
                                type="button"
                                aria-label="dismiss notification"
                                x-on:click="dismiss"
                            >
                                <svg
                                    class="size-5 shrink-0"
                                    aria-hidden="true"
                                    xmlns="http://www.w3.org/2000/svg"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                    fill="none"
                                    stroke-width="2"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M6 18L18 6M6 6l12 12"
                                    />
                                </svg>
                            </button>
                        </div>
                    </div>
                </template>

                <!-- Warning Notification  -->
                <template x-if="notification.variant === 'warning'">
                    <div
                        class="rounded-radius border-warning bg-surface text-on-surface dark:bg-surface-dark dark:text-on-surface-dark toastr-notification pointer-events-auto relative border"
                        role="alert"
                        x-data="toastrNotification(notification, displayDuration, removeNotification)"
                        x-cloak
                        x-show="isVisible"
                        x-on:pause-auto-dismiss.window="pause"
                        x-on:resume-auto-dismiss.window="resume"
                        x-transition:enter="transition duration-300 ease-out"
                        x-transition:enter-end="translate-y-0"
                        x-transition:enter-start="translate-y-8"
                        x-transition:leave="transition duration-300 ease-in"
                        x-transition:leave-end="-translate-x-24 opacity-0 md:translate-x-24"
                        x-transition:leave-start="translate-x-0 opacity-100"
                    >
                        <div
                            class="bg-warning/10 rounded-radius flex w-full items-center gap-2.5 p-4 transition-all duration-300">

                            <!-- Icon -->
                            <div
                                class="bg-warning/15 text-warning rounded-full p-0.5"
                                aria-hidden="true"
                            >
                                <svg
                                    class="size-5"
                                    aria-hidden="true"
                                    xmlns="http://www.w3.org/2000/svg"
                                    viewBox="0 0 20 20"
                                    fill="currentColor"
                                >
                                    <path
                                        fill-rule="evenodd"
                                        d="M18 10a8 8 0 1 1-16 0 8 8 0 0 1 16 0Zm-8-5a.75.75 0 0 1 .75.75v4.5a.75.75 0 0 1-1.5 0v-4.5A.75.75 0 0 1 10 5Zm0 10a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z"
                                        clip-rule="evenodd"
                                    />
                                </svg>
                            </div>

                            <!-- Title & Message -->
                            <div class="flex flex-col gap-2">
                                <h3
                                    class="text-warning text-sm font-semibold"
                                    x-cloak
                                    x-show="notification.title"
                                    x-text="notification.title"
                                ></h3>
                                <p
                                    class="text-pretty text-sm"
                                    x-cloak
                                    x-show="notification.message"
                                    x-text="notification.message"
                                ></p>
                            </div>

                            <!--Dismiss Button -->
                            <button
                                class="ml-auto"
                                type="button"
                                aria-label="dismiss notification"
                                x-on:click="dismiss"
                            >
                                <svg
                                    class="size-5 shrink-0"
                                    aria-hidden="true"
                                    xmlns="http://www.w3.org/2000/svg"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                    fill="none"
                                    stroke-width="2"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M6 18L18 6M6 6l12 12"
                                    />
                                </svg>
                            </button>
                        </div>
                    </div>
                </template>

                <!-- Danger Notification  -->
                <template x-if="notification.variant === 'danger'">
                    <div
                        class="rounded-radius border-danger bg-surface text-on-surface dark:bg-surface-dark dark:text-on-surface-dark toastr-notification pointer-events-auto relative border"
                        role="alert"
                        x-data="toastrNotification(notification, displayDuration, removeNotification)"
                        x-cloak
                        x-show="isVisible"
                        x-on:pause-auto-dismiss.window="pause"
                        x-on:resume-auto-dismiss.window="resume"
                        x-transition:enter="transition duration-300 ease-out"
                        x-transition:enter-end="translate-y-0"
                        x-transition:enter-start="translate-y-8"
                        x-transition:leave="transition duration-300 ease-in"
                        x-transition:leave-end="-translate-x-24 opacity-0 md:translate-x-24"
                        x-transition:leave-start="translate-x-0 opacity-100"
                    >
                        <div
                            class="bg-danger/10 rounded-radius flex w-full items-center gap-2.5 p-4 transition-all duration-300">

                            <!-- Icon -->
                            <div
                                class="bg-danger/15 text-danger rounded-full p-0.5"
                                aria-hidden="true"
                            >
                                <svg
                                    class="size-5"
                                    aria-hidden="true"
                                    xmlns="http://www.w3.org/2000/svg"
                                    viewBox="0 0 20 20"
                                    fill="currentColor"
                                >
                                    <path
                                        fill-rule="evenodd"
                                        d="M18 10a8 8 0 1 1-16 0 8 8 0 0 1 16 0Zm-8-5a.75.75 0 0 1 .75.75v4.5a.75.75 0 0 1-1.5 0v-4.5A.75.75 0 0 1 10 5Zm0 10a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z"
                                        clip-rule="evenodd"
                                    />
                                </svg>
                            </div>

                            <!-- Title & Message -->
                            <div class="flex flex-col gap-2">
                                <h3
                                    class="text-danger text-sm font-semibold"
                                    x-cloak
                                    x-show="notification.title"
                                    x-text="notification.title"
                                ></h3>
                                <p
                                    class="text-pretty text-sm"
                                    x-cloak
                                    x-show="notification.message"
                                    x-text="notification.message"
                                ></p>
                            </div>

                            <!--Dismiss Button -->
                            <button
                                class="ml-auto"
                                type="button"
                                aria-label="dismiss notification"
                                x-on:click="dismiss"
                            >
                                <svg
                                    class="size-5 shrink-0"
                                    aria-hidden="true"
                                    xmlns="http://www.w3.org/2000/svg"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                    fill="none"
                                    stroke-width="2"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M6 18L18 6M6 6l12 12"
                                    />
                                </svg>
                            </button>
                        </div>
                    </div>
                </template>

                <!-- Message Notification  -->
                <template x-if="notification.variant === 'message'">
                    <div
                        class="rounded-radius border-outline bg-surface text-on-surface dark:border-outline-dark dark:bg-surface-dark dark:text-on-surface-dark toastr-notification pointer-events-auto relative border"
                        role="alert"
                        x-data="toastrNotification(notification, displayDuration, removeNotification)"
                        x-cloak
                        x-show="isVisible"
                        x-on:pause-auto-dismiss.window="pause"
                        x-on:resume-auto-dismiss.window="resume"
                        x-transition:enter="transition duration-300 ease-out"
                        x-transition:enter-end="translate-y-0"
                        x-transition:enter-start="translate-y-8"
                        x-transition:leave="transition duration-300 ease-in"
                        x-transition:leave-end="-translate-x-24 opacity-0 md:translate-x-24"
                        x-transition:leave-start="translate-x-0 opacity-100"
                    >
                        <div
                            class="rounded-radius bg-surface-alt dark:bg-surface-dark-alt flex w-full items-center gap-2.5 p-4 transition-all duration-300">
                            <div class="flex w-full items-center gap-2.5">

                                <!-- Avatar -->
                                <img
                                    class="mr-2 size-12 rounded-full"
                                    alt="avatar"
                                    aria-hidden="true"
                                    x-cloak
                                    x-show="notification.sender.avatar"
                                    x-bind:src="notification.sender.avatar"
                                />
                                <div class="flex flex-col items-start gap-2">
                                    <!-- Title & Message -->
                                    <h3
                                        class="text-on-surface-strong dark:text-on-surface-dark-strong text-sm font-semibold"
                                        x-cloak
                                        x-show="notification.sender.name"
                                        x-text="notification.sender.name"
                                    ></h3>
                                    <p
                                        class="text-pretty text-sm"
                                        x-cloak
                                        x-show="notification.message"
                                        x-text="notification.message"
                                    ></p>

                                    <!-- Action Buttons -->
                                    <div class="flex items-center gap-4">
                                        <button
                                            class="text-primary dark:text-primary-dark whitespace-nowrap bg-transparent text-center text-sm font-bold tracking-wide transition hover:opacity-75 active:opacity-100"
                                            type="button"
                                        >Reply</button>
                                        <button
                                            class="text-on-surface dark:text-on-surface-dark whitespace-nowrap bg-transparent text-center text-sm font-bold tracking-wide transition hover:opacity-75 active:opacity-100"
                                            type="button"
                                            x-on:click="dismissDelayed()"
                                        >Dismiss</button>
                                    </div>
                                </div>
                            </div>

                            <!-- Dismiss Button -->
                            <button
                                class="ml-auto"
                                type="button"
                                aria-label="dismiss notification"
                                x-on:click="dismiss"
                            >
                                <svg
                                    class="size-5 shrink-0"
                                    aria-hidden="true"
                                    xmlns="http://www.w3.org/2000/svg"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                    fill="none"
                                    stroke-width="2"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M6 18L18 6M6 6l12 12"
                                    />
                                </svg>
                            </button>
                        </div>
                    </div>
                </template>

            </div>
        </template>
    </div>
</div>

<script nonce="{{ csp_nonce() }}">
    (() => {
        const register = () => {
            Alpine.data('toastrNotification', (notification, displayDuration, removeNotification) => ({
                isVisible: false,
                timeout: null,
                init() {
                    this.$nextTick(() => {
                        this.isVisible = true;
                    });
                    this.timeout = setTimeout(() => {
                        this.isVisible = false;
                        removeNotification(notification.id);
                    }, displayDuration);
                },
                pause() {
                    clearTimeout(this.timeout);
                },
                resume() {
                    this.timeout = setTimeout(() => {
                        this.isVisible = false;
                        removeNotification(notification.id);
                    }, displayDuration);
                },
                dismiss() {
                    this.isVisible = false;
                    removeNotification(notification.id);
                },
                dismissDelayed(delay = 400) {
                    this.isVisible = false;
                    setTimeout(() => removeNotification(notification.id), delay);
                },
            }));

            Alpine.data('toastrRoot', (flashNotifications) => ({
                notifications: [],
                displayDuration: 8000,
                soundEffect: false,

                initFlashNotifications() {
                    flashNotifications.forEach((notification) => this.addNotification(
                    notification));
                },

                handleNotifyEvent(event) {
                    const detail = Array.isArray(event.detail) ? event.detail[0] : event.detail;
                    this.addNotification({
                        variant: detail.variant,
                        sender: detail.sender,
                        title: detail.title,
                        message: detail.message,
                    });
                },

                addNotification({
                    variant = 'info',
                    sender = null,
                    title = null,
                    message = null
                }) {
                    const id = Date.now()
                    const notification = {
                        id,
                        variant,
                        sender,
                        title,
                        message
                    }

                    // Keep only the most recent 20 notifications
                    if (this.notifications.length >= 20) {
                        this.notifications.splice(0, this.notifications.length - 19)
                    }

                    // Add the new notification to the notifications stack
                    this.notifications.push(notification)

                    if (this.soundEffect) {
                        // Play the notification sound
                        const notificationSound = new Audio(
                            'https://res.cloudinary.com/ds8pgw1pf/video/upload/v1728571480/penguinui/component-assets/sounds/ding.mp3'
                        )
                        notificationSound.play().catch((error) => {
                            console.error('Error playing the sound:', error)
                        })
                    }
                },
                removeNotification(id) {
                    setTimeout(() => {
                        this.notifications = this.notifications.filter(
                            (notification) => notification.id !== id,
                        )
                    }, 400);
                },
            }));
        };
        document.addEventListener('alpine:init', register);
        if (window.Alpine) {
            register();
        }
    })();
</script>
