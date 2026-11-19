<?php

namespace Database\Seeders;

use App\Models\Puzzle;
use App\Models\PuzzleClue;
use App\Services\GridBuilder;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * The three puzzles from new_puzzle_1026.pptx: Employer's Rights, Employment Law,
 * and Domestic Inquiry / IR. The deck had no answer key, so the answers were worked
 * out from the clues and checked against the grid shapes (word lengths + shared letters).
 *
 * Safe to run more than once: a puzzle whose title already exists is skipped, so edits
 * made later in the admin screen are never overwritten.
 */
class NewPuzzlesSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->puzzles() as $def) {
            if (Puzzle::where('title', $def['title'])->exists()) {
                continue;
            }

            // Throws if a word runs off the grid or two words disagree on a shared letter.
            GridBuilder::build($def['clues'], $def['rows'], $def['cols']);

            DB::transaction(function () use ($def) {
                $puzzle = Puzzle::create([
                    'title' => $def['title'],
                    'category' => $def['category'],
                    'rows' => $def['rows'],
                    'cols' => $def['cols'],
                    'is_published' => true,
                ]);

                foreach ($def['clues'] as $c) {
                    PuzzleClue::create([
                        'puzzle_id' => $puzzle->id,
                        'number' => $c['number'],
                        'direction' => $c['direction'],
                        'start_row' => $c['start_row'],
                        'start_col' => $c['start_col'],
                        'answer' => GridBuilder::cleanAnswer($c['answer']),
                        'clue_text' => $c['clue_text'],
                    ]);
                }
            });
        }
    }

    private function puzzles(): array
    {
        return [

            [
                'title' => 'Employer’s Rights',
                'category' => 'Employment Act',
                'rows' => 17,
                'cols' => 16,
                'clues' => [
                    ['number' => 1, 'direction' => 'down', 'start_row' => 0, 'start_col' => 6, 'answer' => 'PROBATIONER',
                        'clue_text' => 'Not defined in the EA or IRA; this employee stays in this status until a letter of confirmation is received.'],
                    ['number' => 2, 'direction' => 'down', 'start_row' => 0, 'start_col' => 8, 'answer' => 'FORFEITED',
                        'clue_text' => 'According to E.A.1955, what happens to annual leave not taken within 12 months after the end of each 12 months of continuous service.'],
                    ['number' => 3, 'direction' => 'down', 'start_row' => 3, 'start_col' => 11, 'answer' => 'CONCILIATION',
                        'clue_text' => 'Process the Industrial Relations Department uses to help the parties find a solution to a dismissal dispute (starts with C).'],
                    ['number' => 5, 'direction' => 'down', 'start_row' => 4, 'start_col' => 13, 'answer' => 'PATERNITY',
                        'clue_text' => 'Seven consecutive days of paid leave for a married male employee, limited to 5 confinements.'],
                    ['number' => 6, 'direction' => 'down', 'start_row' => 4, 'start_col' => 15, 'answer' => 'ADVANCE',
                        'clue_text' => 'Must not exceed wages earned in the preceding month, unless it is for purposes such as buying a house or computer, or paying medical expenses.'],
                    ['number' => 7, 'direction' => 'down', 'start_row' => 7, 'start_col' => 3, 'answer' => 'MISCONDUCT',
                        'clue_text' => 'An incident where after due inquiry, an employer may dismiss without notice, downgrade or impose a lesser punishment.'],
                    ['number' => 4, 'direction' => 'across', 'start_row' => 4, 'start_col' => 5, 'answer' => 'WAGES',
                        'clue_text' => 'Includes basic pay and all other cash payments, but not annual bonus, travelling allowance or gratuity.'],
                    ['number' => 8, 'direction' => 'across', 'start_row' => 7, 'start_col' => 6, 'answer' => 'OVERTIME',
                        'clue_text' => 'Capped at 104 hours in any one month, excluding work on rest days and public holidays.'],
                    ['number' => 9, 'direction' => 'across', 'start_row' => 10, 'start_col' => 5, 'answer' => 'PREROGATIVE',
                        'clue_text' => 'Management’s right to decide on employment, promotion, transfer and allocation of duties.'],
                    ['number' => 10, 'direction' => 'across', 'start_row' => 12, 'start_col' => 5, 'answer' => 'MATERNITY',
                        'clue_text' => '98 consecutive days of paid leave in respect of each surviving children, up to maximum of 5.'],
                    ['number' => 11, 'direction' => 'across', 'start_row' => 14, 'start_col' => 2, 'answer' => 'SUSPENSION',
                        'clue_text' => 'For the purpose of a due inquiry, the employer may impose this for not more than 2 weeks, paying not less than half wages.'],
                    ['number' => 12, 'direction' => 'across', 'start_row' => 16, 'start_col' => 0, 'answer' => 'ROSTER',
                        'clue_text' => 'The employer must display this list of paid public holidays conspicuously before the start of each year.'],
                ],
            ],
            [
                'title' => 'Employment Law',
                'category' => 'Employment Act',
                'rows' => 16,
                'cols' => 12,
                'clues' => [
                    ['number' => 1, 'direction' => 'down', 'start_row' => 0, 'start_col' => 0, 'answer' => 'SHOWCAUSE',
                        'clue_text' => 'Letter that gives the employee the right to put forth a defence before disciplinary action is decided.'],
                    ['number' => 2, 'direction' => 'down', 'start_row' => 0, 'start_col' => 8, 'answer' => 'OVERTIME',
                        'clue_text' => 'Limited to a total of 104 hours in any one month, excluding work on rest days and public holidays.'],
                    ['number' => 3, 'direction' => 'down', 'start_row' => 0, 'start_col' => 11, 'answer' => 'REDUNDANCY',
                        'clue_text' => 'Termination due to ____ or reorganisation is listed under the employer’s prerogative in s.13 IRA 1967.'],
                    ['number' => 4, 'direction' => 'down', 'start_row' => 6, 'start_col' => 2, 'answer' => 'WORKMAN',
                        'clue_text' => 'Under s.2 IRA 1967: any person, including an apprentice, employed under a contract of employment to work for hire or reward.'],
                    ['number' => 5, 'direction' => 'down', 'start_row' => 7, 'start_col' => 6, 'answer' => 'ADVANCE',
                        'clue_text' => 'Under EA s.22, this must not exceed wages earned in the preceding month, unless it is for purposes such as buying a house, land or a computer.'],
                    ['number' => 6, 'direction' => 'down', 'start_row' => 7, 'start_col' => 9, 'answer' => 'SHIFT',
                        'clue_text' => '____ work (s.60C): must not exceeds 12 hours in any one day.'],
                    ['number' => 7, 'direction' => 'down', 'start_row' => 10, 'start_col' => 4, 'answer' => 'NOTICE',
                        'clue_text' => 'Under s.81H, the employer must exhibit this conspicuously at the place of employment to raise awareness on sexual harassment – …………. on Sexual Harassment.'],
                    ['number' => 1, 'direction' => 'across', 'start_row' => 0, 'start_col' => 0, 'answer' => 'SUSPENSION',
                        'clue_text' => 'Pending a domestic inquiry, the employer may impose this for not more than 2 weeks, paying not less than half wages (EA s.14(2)). It is not a punishment.'],
                    ['number' => 2, 'direction' => 'across', 'start_row' => 2, 'start_col' => 2, 'answer' => 'REGISTER',
                        'clue_text' => 'Under E.A.1955, every employer must prepare and keep an employees’ ____ at the place where the employees are employed.'],
                    ['number' => 3, 'direction' => 'across', 'start_row' => 4, 'start_col' => 0, 'answer' => 'CONCILIATION',
                        'clue_text' => 'Process by which the Industrial Relations Department helps the parties find a solution to a dismissal dispute (starts with C).'],
                    ['number' => 4, 'direction' => 'across', 'start_row' => 7, 'start_col' => 5, 'answer' => 'WAGES',
                        'clue_text' => 'Includes basic pay and all other payments in cash payable to an employee, but excluding items such as annual bonus and travelling allowance.'],
                    ['number' => 5, 'direction' => 'across', 'start_row' => 10, 'start_col' => 2, 'answer' => 'MANUAL',
                        'clue_text' => 'Colgate Palmolive v Foo Cheong Weng (2002): ____ work involves physical strength rather than the effort of intelligence or thinking.'],
                    ['number' => 6, 'direction' => 'across', 'start_row' => 13, 'start_col' => 3, 'answer' => 'FIDELITY',
                        'clue_text' => 'Implied duty requiring the employee to serve faithfully and in good faith, and not to misuse or disclose the employer’s information (starts with F).'],
                    ['number' => 7, 'direction' => 'across', 'start_row' => 15, 'start_col' => 2, 'answer' => 'FLEXIBLE',
                        'clue_text' => '____ working arrangement (s.60P): employee may apply to vary hours, days or place of work; employer must reply in writing within 60 days.'],
                ],
            ],
            [
                'title' => 'Domestic Inquiry / IR',
                'category' => 'Termination of Employment / IR',
                'rows' => 15,
                'cols' => 17,
                'clues' => [
                    ['number' => 1, 'direction' => 'down', 'start_row' => 0, 'start_col' => 8, 'answer' => 'SUSPENSION',
                        'clue_text' => 'Pending inquiry, this is not a punishment and must not exceed 2 weeks under EA 1955 s.14(2).'],
                    ['number' => 2, 'direction' => 'down', 'start_row' => 0, 'start_col' => 12, 'answer' => 'PANEL',
                        'clue_text' => 'DI members who should ideally be an odd number and equal or higher in rank than the accused.'],
                    ['number' => 3, 'direction' => 'down', 'start_row' => 1, 'start_col' => 16, 'answer' => 'CHARGESHEET',
                        'clue_text' => 'If this document is vague and lacks particulars, the inquiry breaches natural justice.'],
                    ['number' => 4, 'direction' => 'down', 'start_row' => 2, 'start_col' => 10, 'answer' => 'CONDONATION',
                        'clue_text' => 'Unreasonable delay by the employer in acting against a misconduct may amount to this.'],
                    ['number' => 5, 'direction' => 'down', 'start_row' => 6, 'start_col' => 2, 'answer' => 'VERBATIM',
                        'clue_text' => 'How the DI recorder or secretary should record the proceedings.'],
                    ['number' => 6, 'direction' => 'down', 'start_row' => 8, 'start_col' => 6, 'answer' => 'EXPARTE',
                        'clue_text' => 'If the accused fails to attend the DI at the stated time, date and place, the inquiry proceeds this way.'],
                    ['number' => 7, 'direction' => 'down', 'start_row' => 11, 'start_col' => 12, 'answer' => 'HALF',
                        'clue_text' => 'During suspension for an inquiry, the employer must pay not less than this portion of the employee’s wages.'],
                    ['number' => 1, 'direction' => 'across', 'start_row' => 3, 'start_col' => 6, 'answer' => 'EMPLOYER',
                        'clue_text' => 'In a dismissal case, the burden of proof lies on this party to show just cause and excuse.'],
                    ['number' => 2, 'direction' => 'across', 'start_row' => 6, 'start_col' => 8, 'answer' => 'SHOWCAUSE',
                        'clue_text' => 'Letter asking the accused employee to explain why disciplinary action should not be taken.'],
                    ['number' => 3, 'direction' => 'across', 'start_row' => 8, 'start_col' => 4, 'answer' => 'PREROGATIVE',
                        'clue_text' => 'Management’s right to regulate hiring, work assignments and transfers (IRA 1967).'],
                    ['number' => 4, 'direction' => 'across', 'start_row' => 10, 'start_col' => 0, 'answer' => 'FRAUD',
                        'clue_text' => 'A general allegation of this misconduct is insufficient; the employer must obtain clear proof. Name this misconduct.'],
                    ['number' => 5, 'direction' => 'across', 'start_row' => 12, 'start_col' => 1, 'answer' => 'LIFO',
                        'clue_text' => 'Common retrenchment selection principle: the most junior in a category goes first (acronym).'],
                    ['number' => 6, 'direction' => 'across', 'start_row' => 12, 'start_col' => 6, 'answer' => 'REDUNDANCY',
                        'clue_text' => 'Situation where the employee’s job functions are surplus to the operational requirements of the business.'],
                    ['number' => 7, 'direction' => 'across', 'start_row' => 14, 'start_col' => 5, 'answer' => 'DENOVO',
                        'clue_text' => 'The Industrial Court rehears the case afresh, so its hearing is described as …………. [DE NOVO – type it as one word]'],
                ],
            ],
        ];
    }
}
