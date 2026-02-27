import { readdirSync, readFileSync, statSync } from "node:fs";
import { join } from "node:path";

import { describe, expect, it } from "vitest";

const BASELINE_UNKNOWN_USAGE_COUNT = 5;

const collectFiles = (directory: string): string[] => {
    const entries = readdirSync(directory);
    const files: string[] = [];

    for (const entry of entries) {
        const fullPath = join(directory, entry);
        const stats = statSync(fullPath);

        if (stats.isDirectory()) {
            files.push(...collectFiles(fullPath));
            continue;
        }

        if (fullPath.endsWith(".ts")) {
            files.push(fullPath);
        }
    }

    return files;
};

const countUnknownLines = (directories: string[]): number => {
    let count = 0;

    for (const directory of directories) {
        for (const file of collectFiles(directory)) {
            const source = readFileSync(file, "utf8");
            const lines = source.split(/\r?\n/);
            count += lines.filter((line) => /\bunknown\b/.test(line)).length;
        }
    }

    return count;
};

describe("frontend dto baseline", () => {
    it("does not increase unknown usage in api and mapper layer", () => {
        const count = countUnknownLines(["resources/js/api", "resources/js/mappers"]);

        expect(count).toBeLessThanOrEqual(BASELINE_UNKNOWN_USAGE_COUNT);
    });
});
