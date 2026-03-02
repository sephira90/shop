import { defineStore } from "pinia";

export interface AppShellNotice {
    type: "success" | "error";
    message: string;
}

let clearNoticeTimer: number | null = null;

export const useAppShellStore = defineStore("app-shell", {
    state: () => ({
        notice: {
            type: "error" as const,
            message: "",
        } satisfies AppShellNotice,
    }),
    actions: {
        clearNotice(): void {
            if (clearNoticeTimer !== null) {
                window.clearTimeout(clearNoticeTimer);
                clearNoticeTimer = null;
            }

            this.notice.type = "error";
            this.notice.message = "";
        },
        showError(message: string, durationMs = 5000): void {
            this.clearNotice();
            this.notice.type = "error";
            this.notice.message = message;

            if (durationMs > 0 && typeof window !== "undefined") {
                clearNoticeTimer = window.setTimeout(() => {
                    this.clearNotice();
                }, durationMs);
            }
        },
    },
});
